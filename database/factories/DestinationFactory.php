<?php

namespace Database\Factories;

use App\Models\Destination;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Destination>
 */
class DestinationFactory extends Factory
{
    protected $model = Destination::class;

    public function definition(): array
    {
        return [
            'name' => $name = fake()->unique()->randomElement(['Everest', 'Annapurna', 'Langtang', 'Manaslu', 'Mustang', 'Dolpo', 'Kanchenjunga', 'Makalu', 'Rolwaling', 'Tsum Valley']).' '.fake()->unique()->numberBetween(1, 99999),
            'slug' => Str::slug($name),
            'tagline' => fake()->sentence(4),
            'intro' => fake()->paragraph(),
            'description' => '<p>'.fake()->paragraphs(3, true).'</p>',
            'highlights' => fake()->sentences(3),
            'is_featured' => false,
            'sort' => 0,
        ];
    }

    public function featured(): static
    {
        return $this->state(['is_featured' => true]);
    }

    public function childOf(Destination $parent): static
    {
        return $this->state(['parent_id' => $parent->id]);
    }
}
