<?php

namespace Database\Factories;

use App\Models\Activity;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Activity>
 */
class ActivityFactory extends Factory
{
    protected $model = Activity::class;

    public function definition(): array
    {
        return [
            'name' => $name = fake()->unique()->randomElement(['Trekking', 'Peak Climbing', 'Expedition', 'Tours', 'Rafting', 'Jungle Safari', 'Heli Tours']).' '.fake()->unique()->numberBetween(1, 99999),
            'slug' => Str::slug($name),
            'tagline' => fake()->sentence(4),
            'description' => '<p>'.fake()->paragraph().'</p>',
            'is_featured' => false,
            'sort' => 0,
        ];
    }
}
