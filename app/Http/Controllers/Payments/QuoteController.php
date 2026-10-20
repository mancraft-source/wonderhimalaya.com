<?php

namespace App\Http\Controllers\Payments;

use App\Actions\Bookings\CreateBookingFromQuote;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Enums\QuoteStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Quote;
use App\Models\Setting;
use App\Payments\PaymentService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/** Public quote pages (link e-mailed to the traveller; see App\Notifications\QuoteSent). */
class QuoteController extends Controller
{
    public function show(Request $request, string $uuid): View
    {
        $quote = $this->find($request, $uuid);

        // Staff previewing from the CRM must not flip the status.
        if (! $request->user() && $quote->status === QuoteStatus::Sent && ! $quote->isExpired()) {
            $quote->forceFill(['status' => QuoteStatus::Viewed, 'viewed_at' => $quote->viewed_at ?? now()])->save();
        } elseif (! $request->user() && $quote->viewed_at === null && $quote->status !== QuoteStatus::Draft) {
            $quote->forceFill(['viewed_at' => now()])->save();
        }

        $quote->load(['items', 'trip.days', 'trip.media', 'tier', 'departure', 'contact', 'creator', 'booking']);

        return view('quotes.show', [
            'quote' => $quote,
            'pendingPayment' => $this->pendingPayment($quote->booking),
            'company' => $this->company(),
        ]);
    }

    public function pdf(Request $request, string $uuid): Response
    {
        $quote = $this->find($request, $uuid)->load(['items', 'trip.days', 'tier', 'departure', 'contact', 'creator']);

        return Pdf::loadView('quotes.pdf', ['quote' => $quote, 'company' => $this->company()])
            ->setPaper('a4')
            ->download($quote->number.'.pdf');
    }

    public function accept(Request $request, string $uuid, CreateBookingFromQuote $createBooking, PaymentService $payments): RedirectResponse
    {
        $request->validate(['accept_terms' => ['accepted']], ['accept_terms.accepted' => 'Please confirm you accept the terms and conditions.']);

        $quote = $this->find($request, $uuid);

        $payment = DB::transaction(function () use ($quote, $createBooking, $payments) {
            /** @var Quote $locked */
            $locked = Quote::query()->whereKey($quote->id)->lockForUpdate()->firstOrFail();

            // Double submit / back button: reuse the booking and its open deposit link.
            if ($locked->status === QuoteStatus::Accepted && ($booking = Booking::where('quote_id', $locked->id)->first())) {
                return $this->pendingPayment($booking);
            }

            if (! $locked->isAcceptable()) {
                return null;
            }

            $locked->forceFill(['status' => QuoteStatus::Accepted, 'accepted_at' => now()])->save();
            $booking = $createBooking->handle($locked);
            $amount = (float) $locked->deposit_amount > 0 ? (float) $locked->deposit_amount : (float) $locked->total;

            $locked->lead?->notes()->create(['body' => "Quote {$locked->number} accepted online by the traveller; booking {$booking->number} created."]);

            return $amount > 0 ? $payments->createLink($booking, $amount, PaymentType::Deposit) : null;
        });

        if (! $payment) {
            return redirect()->route('quotes.show', $uuid)
                ->with('error', $quote->fresh()->status === QuoteStatus::Accepted
                    ? 'This quote has already been accepted. We will be in touch shortly.'
                    : 'This quote can no longer be accepted. Please contact us for an updated quote.');
        }

        return redirect()->route('pay.show', $payment);
    }

    public function decline(Request $request, string $uuid): RedirectResponse
    {
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:1000']]);
        $quote = $this->find($request, $uuid);

        if (in_array($quote->status, [QuoteStatus::Sent, QuoteStatus::Viewed], true)) {
            $quote->forceFill(['status' => QuoteStatus::Declined, 'declined_at' => now()])->save();

            $quote->lead?->notes()->create([
                'body' => "Quote {$quote->number} declined online by the traveller.".(! empty($data['reason']) ? "\nReason: ".$data['reason'] : ''),
            ]);
        }

        return redirect()->route('quotes.show', $uuid)->with('status', 'Thank you for letting us know. Your trip specialist will be in touch if we can help further.');
    }

    private function find(Request $request, string $uuid): Quote
    {
        $quote = Quote::where('uuid', $uuid)->firstOrFail();

        // Drafts are only visible to logged-in staff (preview).
        abort_if($quote->status === QuoteStatus::Draft && ! $request->user(), 404);

        return $quote;
    }

    private function pendingPayment(?Booking $booking): ?Payment
    {
        return $booking?->payments()
            ->where('status', PaymentStatus::Pending)
            ->get()
            ->first(fn (Payment $p) => $p->isPayable());
    }

    /** @return array<string, mixed> */
    private function company(): array
    {
        $company = Setting::get('company', []);

        return is_array($company) ? $company : [];
    }
}
