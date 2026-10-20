<?php

namespace App\Payments;

use App\Actions\Bookings\CreateBookingFromQuote;
use App\Enums\BookingStatus;
use App\Enums\LeadStatus;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Enums\QuoteStatus;
use App\Events\BookingConfirmed;
use App\Events\PaymentSucceeded;
use App\Models\Booking;
use App\Models\Lead;
use App\Models\Payment;
use App\Models\Quote;
use App\Models\User;
use App\Notifications\BookingConfirmed as BookingConfirmedNotification;
use App\Notifications\PaymentReceived;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * The single place that changes payment state. Every gateway path (return, webhook,
 * manual staff action) funnels through markPaid()/markFailed(), which are idempotent.
 */
class PaymentService
{
    /** Lead statuses that may move forward to "deposit paid". */
    private const PRE_DEPOSIT_LEAD_STATUSES = [LeadStatus::New, LeadStatus::Contacted, LeadStatus::Qualified, LeadStatus::Quoted, LeadStatus::Lost];

    public function __construct(private readonly CreateBookingFromQuote $createBooking) {}

    /** Create a pending payment link for a booking or quote. Amount is decided server-side by the caller. */
    public function createLink(Booking|Quote $payable, float $amount, PaymentType $type, ?User $creator = null, ?string $description = null): Payment
    {
        $payable->loadMissing('trip');

        return $payable->payments()->create([
            'contact_id' => $payable->contact_id,
            'created_by_id' => $creator?->id,
            'type' => $type,
            'description' => $description ?? $this->describe($payable, $type),
            'amount' => round($amount, 2),
            'currency' => $payable->currency,
            'status' => PaymentStatus::Pending,
            'expires_at' => now()->addDays(max(1, (int) config('payments.link_ttl_days', 14))),
        ]);
    }

    /**
     * Apply a verified gateway result. Re-checks webhook-reported amounts against the Payment.
     */
    public function apply(Payment $payment, string $gateway, PaymentResult $result): void
    {
        if ($result->isPaid() && $result->amount !== null && ! PaymentResult::amountMatches($payment, $result->amount, $result->currency)) {
            $result = PaymentResult::mismatch('amount/currency mismatch', $result->payload, $result->reference);
        }

        match ($result->status) {
            PaymentResult::PAID => $this->markPaid($payment, $gateway, (string) $result->reference, $result->payload),
            PaymentResult::FAILED => $this->markFailed($payment, $gateway, $result->message, $result->payload),
            PaymentResult::MISMATCH => PaymentLog::error('payment.mismatch', [
                'payment' => $payment->uuid, 'gateway' => $gateway, 'reason' => $result->message, 'reference' => $result->reference,
            ]),
            default => PaymentLog::info('payment.'.$result->status, ['payment' => $payment->uuid, 'gateway' => $gateway]),
        };
    }

    /**
     * Mark a payment as paid. Safe to call concurrently / repeatedly (return + webhook):
     * only the first call changes anything. Returns true when this call transitioned it.
     *
     * @param  array<string, mixed>  $payload
     */
    public function markPaid(Payment $payment, string $gateway, string $reference, array $payload = []): bool
    {
        $confirmedBooking = null;

        $transitioned = DB::transaction(function () use ($payment, $gateway, $reference, $payload, &$confirmedBooking) {
            /** @var Payment $locked */
            $locked = Payment::query()->whereKey($payment->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->status === PaymentStatus::Paid) {
                return false;
            }

            $locked->forceFill([
                'status' => PaymentStatus::Paid,
                'gateway' => $gateway,
                'gateway_reference' => $reference !== '' ? $reference : $locked->gateway_reference,
                'gateway_payload' => PaymentLog::scrub(array_merge($locked->gateway_payload ?? [], ['result' => $payload])),
                'paid_at' => now(),
            ])->save();

            $payable = $locked->payable;

            if ($payable instanceof Quote) {
                $payable = $this->convertQuote($payable, $locked);
            }

            if ($payable instanceof Booking) {
                $confirmedBooking = $this->syncBooking($payable, $locked);
            }

            $payment->setRawAttributes($locked->getAttributes(), true);
            $payment->setRelation('payable', $payable);

            return true;
        });

        if (! $transitioned) {
            PaymentLog::info('payment.already_paid', ['payment' => $payment->uuid, 'gateway' => $gateway]);

            return false;
        }

        PaymentLog::info('payment.paid', ['payment' => $payment->uuid, 'gateway' => $gateway, 'reference' => $reference, 'amount' => $payment->amount]);

        PaymentSucceeded::dispatch($payment);
        $this->sendReceipt($payment);

        if ($confirmedBooking) {
            BookingConfirmed::dispatch($confirmedBooking);
            $this->sendConfirmation($confirmedBooking);
        }

        return true;
    }

    /** @param array<string, mixed> $payload */
    public function markFailed(Payment $payment, string $gateway, ?string $reason = null, array $payload = []): bool
    {
        return DB::transaction(function () use ($payment, $gateway, $reason, $payload) {
            $locked = Payment::query()->whereKey($payment->getKey())->lockForUpdate()->firstOrFail();

            // A failed attempt never overrides a success. The link stays usable for a retry
            // (status back to pending) unless it has expired.
            if ($locked->status !== PaymentStatus::Pending) {
                return false;
            }

            $attempts = $locked->gateway_payload['failures'] ?? [];
            $attempts[] = ['gateway' => $gateway, 'reason' => $reason, 'at' => now()->toIso8601String(), 'payload' => PaymentLog::scrub($payload)];

            $locked->forceFill([
                'gateway' => $gateway,
                'gateway_payload' => array_merge($locked->gateway_payload ?? [], ['failures' => array_slice($attempts, -10)]),
                'status' => $locked->expires_at?->isPast() ? PaymentStatus::Failed : PaymentStatus::Pending,
            ])->save();

            $payment->setRawAttributes($locked->getAttributes(), true);
            PaymentLog::warning('payment.failed', ['payment' => $payment->uuid, 'gateway' => $gateway, 'reason' => $reason]);

            return true;
        });
    }

    /** Find or create the open balance link for a booking (used by reminders). */
    public function balanceLinkFor(Booking $booking): ?Payment
    {
        $balance = $booking->balanceDue();

        if ($balance <= 0) {
            return null;
        }

        $existing = $booking->payments()
            ->where('status', PaymentStatus::Pending)
            ->where('type', PaymentType::Balance)
            ->get()
            ->first(fn (Payment $p) => $p->isPayable() && abs((float) $p->amount - $balance) < 0.01);

        return $existing ?? $this->createLink($booking, $balance, PaymentType::Balance);
    }

    private function convertQuote(Quote $quote, Payment $payment): Booking
    {
        if (! in_array($quote->status, [QuoteStatus::Accepted], true)) {
            $quote->forceFill(['status' => QuoteStatus::Accepted, 'accepted_at' => $quote->accepted_at ?? now()])->save();
        }

        $booking = $this->createBooking->handle($quote);
        $payment->payable()->associate($booking)->save();

        return $booking;
    }

    /** Returns the booking when this payment confirmed it (pending deposit → confirmed/paid in full). */
    private function syncBooking(Booking $booking, Payment $payment): ?Booking
    {
        /** @var Booking $booking */
        $booking = Booking::query()->whereKey($booking->getKey())->lockForUpdate()->firstOrFail();
        $previous = $booking->status;

        $booking->syncPaymentTotals();

        $confirmed = $previous === BookingStatus::PendingDeposit
            && in_array($booking->status, [BookingStatus::Confirmed, BookingStatus::PaidInFull], true);

        if ($confirmed && $booking->trip_departure_id) {
            // Seats are taken exactly once: only on the pending → confirmed transition (guarded by the row lock).
            $booking->departure()->increment('seats_booked', max(1, (int) $booking->pax));
        }

        if ($booking->lead_id && in_array($booking->status, [BookingStatus::Confirmed, BookingStatus::PaidInFull], true)) {
            $lead = Lead::find($booking->lead_id);

            if ($lead && in_array($lead->status, self::PRE_DEPOSIT_LEAD_STATUSES, true)) {
                $lead->forceFill(['status' => LeadStatus::DepositPaid, 'converted_at' => $lead->converted_at ?? now()])->save();
            }
        }

        return $confirmed ? $booking : null;
    }

    private function sendReceipt(Payment $payment): void
    {
        $email = $payment->contact?->email ?? $this->payableContactEmail($payment->payable);

        if ($email) {
            Notification::route('mail', $email)->notify(new PaymentReceived($payment));
        }
    }

    private function sendConfirmation(Booking $booking): void
    {
        if ($email = $booking->contact?->email) {
            Notification::route('mail', $email)->notify(new BookingConfirmedNotification($booking));
        }
    }

    private function payableContactEmail(?Model $payable): ?string
    {
        return $payable instanceof Booking || $payable instanceof Quote ? $payable->contact?->email : null;
    }

    private function describe(Booking|Quote $payable, PaymentType $type): string
    {
        $what = $payable->trip?->title ?? ($payable instanceof Quote ? $payable->title : 'Wonder Himalaya trip');

        return $type->getLabel().' — '.$what.' ('.$payable->number.')';
    }
}
