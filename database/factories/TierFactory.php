<?php

namespace Database\Factories;

use App\Models\Tier;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Tier>
 */
class TierFactory extends Factory
{
    protected $model = Tier::class;

    public function definition(): array
    {
        return [
            'name' => $name = fake()->randomElement(['Standard', 'Premium', 'Luxury']).' '.fake()->unique()->numberBetween(1, 99999),
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'sort' => fake()->numberBetween(0, 10),
        ];
    }
}
