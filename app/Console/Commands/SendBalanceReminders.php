<?php

namespace App\Console\Commands;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Notifications\BalancePaymentDue;
use App\Payments\PaymentLog;
use App\Payments\PaymentService;
use App\Support\Notifications\NotificationSettings;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

/**
 * Daily: confirmed bookings whose balance falls due within the reminder window get a
 * (reused) pending balance payment link by e-mail. At most one reminder per booking per day:
 * the send date is recorded on the payment link itself.
 */
#[Signature('bookings:send-balance-reminders {--dry-run : List bookings without sending}')]
#[Description('E-mail balance payment links for bookings due soon')]
class SendBalanceReminders extends Command
{
    public function handle(PaymentService $payments): int
    {
        if (! NotificationSettings::enabled('balance_due')) {
            $this->info('Balance reminders are switched off under Settings → Notifications.');

            return self::SUCCESS;
        }

        $window = (int) config('payments.balance_reminder_window_days', 7);
        $today = today()->toDateString();
        $sent = 0;

        Booking::query()
            ->where('status', BookingStatus::Confirmed)
            ->whereNotNull('balance_due_on')
            ->whereDate('balance_due_on', '<=', today()->addDays($window))
            ->whereColumn('amount_paid', '<', 'total')
            ->with(['contact', 'trip'])
            ->chunkById(100, function ($bookings) use ($payments, $today, &$sent) {
                foreach ($bookings as $booking) {
                    if ($booking->balanceDue() <= 0 || ! $booking->contact?->email) {
                        continue;
                    }

                    if ($this->option('dry-run')) {
                        $this->line("{$booking->number}: ".$booking->balanceDue().' '.$booking->currency);

                        continue;
                    }

                    $payment = $payments->balanceLinkFor($booking);
                    $reminders = $payment->gateway_payload['reminders'] ?? [];

                    if (in_array($today, $reminders, true)) {
                        continue;
                    }

                    Notification::route('mail', $booking->contact->email)->notify(new BalancePaymentDue($booking, $payment));

                    $payment->forceFill(['gateway_payload' => array_merge($payment->gateway_payload ?? [], ['reminders' => [...$reminders, $today]])])->save();
                    PaymentLog::info('balance.reminder_sent', ['booking' => $booking->number, 'payment' => $payment->uuid]);
                    $sent++;
                }
            });

        $this->info("Sent {$sent} balance reminder(s).");

        return self::SUCCESS;
    }
}
