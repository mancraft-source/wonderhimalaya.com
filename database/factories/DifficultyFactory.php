<?php

namespace Database\Factories;

use App\Models\Difficulty;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Difficulty>
 */
class DifficultyFactory extends Factory
{
    protected $model = Difficulty::class;

    public function definition(): array
    {
        return [
            'name' => $name = fake()->randomElement(['Easy', 'Moderate', 'Challenging', 'Strenuous']).' '.fake()->unique()->numberBetween(1, 99999),
            'slug' => Str::slug($name),
            'level' => fake()->numberBetween(1, 5),
            'description' => fake()->sentence(),
        ];
    }
}
