<?php

namespace Database\Factories;

use App\Models\Testimonial;
use App\Models\Trip;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Testimonial>
 */
class TestimonialFactory extends Factory
{
    protected $model = Testimonial::class;

    public function definition(): array
    {
        return [
            'author_name' => fake()->name(),
            'author_country' => fake()->country(),
            'title' => rtrim(fake()->sentence(5), '.'),
            'body' => fake()->paragraphs(2, true),
            'rating' => fake()->randomElement([4, 5, 5, 5]),
            'source' => fake()->randomElement(['google', 'tripadvisor', 'manual']),
            'source_url' => null,
            'reviewed_at' => fake()->dateTimeBetween('-2 years', 'now'),
            'is_featured' => false,
            'is_published' => true,
        ];
    }

    public function featured(): static
    {
        return $this->state(['is_featured' => true]);
    }

    public function unpublished(): static
    {
        return $this->state(['is_published' => false]);
    }

    public function forTrip(Trip $trip): static
    {
        return $this->state(['trip_id' => $trip->getKey()]);
    }

    public function source(string $source): static
    {
        return $this->state(['source' => $source]);
    }
}
