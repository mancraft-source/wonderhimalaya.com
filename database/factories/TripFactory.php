<?php

namespace Database\Factories;

use App\Enums\PublishStatus;
use App\Models\Trip;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Trip>
 */
class TripFactory extends Factory
{
    protected $model = Trip::class;

    public function definition(): array
    {
        return [
            'trip_code' => 'WH-'.fake()->unique()->numberBetween(100, 999999),
            'title' => $title = fake()->randomElement(['Everest Base Camp', 'Annapurna Circuit', 'Langtang Valley', 'Manaslu Circuit', 'Gokyo Lakes', 'Upper Mustang', 'Island Peak Climbing']).' Trek '.fake()->unique()->numberBetween(1, 99999),
            'slug' => Str::slug($title),
            'subtitle' => fake()->sentence(6),
            'excerpt' => fake()->paragraph(),
            'overview' => '<p>'.fake()->paragraphs(2, true).'</p>',
            'duration_days' => $days = fake()->numberBetween(5, 21),
            'duration_nights' => $days - 1,
            'max_altitude_m' => fake()->numberBetween(3000, 6200),
            'group_min' => 1,
            'group_max' => 12,
            'min_age' => 12,
            'best_season' => 'Mar–May, Sep–Nov',
            'accommodation' => 'Tea houses',
            'meals' => 'Full board on trek',
            'transport' => 'Private vehicle / domestic flight',
            'start_point' => 'Kathmandu',
            'end_point' => 'Kathmandu',
            'highlights' => fake()->sentences(4),
            'includes' => fake()->sentences(3),
            'excludes' => fake()->sentences(3),
            'facts' => ['Permits' => 'Included'],
            'videos' => [],
            'price_from' => fake()->randomElement([990, 1290, 1490, 1890, 2450]),
            'sale_price' => null,
            'currency' => 'USD',
            'status' => PublishStatus::Published,
            'published_at' => now()->subDay(),
            'sort' => 0,
        ];
    }

    public function draft(): static
    {
        return $this->state(['status' => PublishStatus::Draft, 'published_at' => null]);
    }

    public function onSale(float $price): static
    {
        return $this->state(['sale_price' => $price]);
    }

    public function featured(): static
    {
        return $this->state(['is_featured' => true]);
    }

    public function fixedDeparture(): static
    {
        return $this->state(['is_fixed_departure' => true]);
    }
}
