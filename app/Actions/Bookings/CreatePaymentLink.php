<?php

namespace App\Actions\Bookings;

use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use InvalidArgumentException;

/** Creates a pending payment request the client pays via /pay/{uuid}. */
class CreatePaymentLink
{
    public const GATEWAYS = ['any' => 'Client chooses', 'paypal' => 'PayPal', 'nepal_bank' => 'Nepal bank (card)'];

    public function suggestedAmount(Booking $booking, PaymentType $type): float
    {
        return match ($type) {
            PaymentType::Deposit => max(0, round((float) $booking->deposit_amount - (float) $booking->amount_paid, 2)) ?: (float) $booking->deposit_amount,
            PaymentType::Balance, PaymentType::Custom => $booking->balanceDue(),
        };
    }

    public function handle(Booking $booking, PaymentType $type, float $amount, ?string $gateway = null, ?int $expiresInDays = 7, ?User $by = null, ?string $description = null): Payment
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Payment amount must be greater than zero.');
        }

        return $booking->payments()->create([
            'contact_id' => $booking->contact_id,
            'created_by_id' => $by?->id,
            'gateway' => ($gateway && $gateway !== 'any') ? $gateway : null,
            'type' => $type,
            'description' => $description ?: $type->getLabel().' for booking '.$booking->number,
            'amount' => round($amount, 2),
            'currency' => $booking->currency ?: 'USD',
            'status' => PaymentStatus::Pending,
            'expires_at' => $expiresInDays ? now()->addDays($expiresInDays)->endOfDay() : null,
        ]);
    }
}
