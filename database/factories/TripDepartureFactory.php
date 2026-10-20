<?php

namespace Database\Factories;

use App\Enums\DepartureStatus;
use App\Models\TripDeparture;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TripDeparture>
 */
class TripDepartureFactory extends Factory
{
    protected $model = TripDeparture::class;

    public function definition(): array
    {
        return [
            'trip_id' => TripFactory::new(),
            'start_date' => $start = fake()->dateTimeBetween('+1 week', '+6 months'),
            'end_date' => (clone $start)->modify('+14 days'),
            'price' => null,
            'sale_price' => null,
            'seats_total' => 12,
            'seats_booked' => fake()->numberBetween(0, 6),
            'status' => DepartureStatus::Available,
            'note' => null,
        ];
    }

    public function past(): static
    {
        return $this->state(fn () => ['start_date' => $start = now()->subMonths(2), 'end_date' => $start->copy()->addDays(14)]);
    }

    public function soldOut(): static
    {
        return $this->state(fn (array $attributes) => ['seats_booked' => $attributes['seats_total'] ?? 12, 'status' => DepartureStatus::SoldOut]);
    }
}
