<?php

namespace Database\Factories;

use App\Models\Redirect;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Redirect>
 */
class RedirectFactory extends Factory
{
    protected $model = Redirect::class;

    public function definition(): array
    {
        return [
            'from_path' => '/'.fake()->unique()->slug(3),
            'to_path' => '/'.fake()->slug(2),
            'status_code' => 301,
            'is_active' => true,
            'hits' => 0,
            'last_hit_at' => null,
        ];
    }

    public function temporary(): static
    {
        return $this->state(['status_code' => 302]);
    }

    public function gone(): static
    {
        return $this->state(['status_code' => 410]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
