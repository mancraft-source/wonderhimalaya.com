<?php

namespace Database\Factories;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    protected $model = Booking::class;

    public function definition(): array
    {
        return [
            'contact_id' => ContactFactory::new(),
            'status' => BookingStatus::PendingDeposit,
            'start_date' => $start = fake()->dateTimeBetween('+1 month', '+6 months'),
            'end_date' => (clone $start)->modify('+14 days'),
            'pax' => 2,
            'currency' => 'USD',
            'total' => 3000,
            'deposit_amount' => 600,
            'amount_paid' => 0,
            'balance_due_on' => (clone $start)->modify('-30 days'),
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => BookingStatus::Confirmed,
            'amount_paid' => $attributes['deposit_amount'] ?? 600,
            'confirmed_at' => now(),
        ]);
    }

    public function past(): static
    {
        return $this->state(fn () => ['start_date' => $start = now()->subMonths(2), 'end_date' => $start->copy()->addDays(14)]);
    }
}
