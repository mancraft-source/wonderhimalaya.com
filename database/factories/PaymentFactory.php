<?php

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'payable_type' => 'booking',
            'payable_id' => BookingFactory::new(),
            'gateway' => null,
            'type' => PaymentType::Deposit,
            'description' => null,
            'amount' => 600,
            'currency' => 'USD',
            'status' => PaymentStatus::Pending,
            'expires_at' => now()->addDays(7),
        ];
    }

    public function forBooking(Booking $booking): static
    {
        return $this->state(['payable_type' => 'booking', 'payable_id' => $booking->id, 'contact_id' => $booking->contact_id, 'currency' => $booking->currency]);
    }

    public function paid(): static
    {
        return $this->state(['status' => PaymentStatus::Paid, 'paid_at' => now(), 'gateway' => 'manual']);
    }
}
