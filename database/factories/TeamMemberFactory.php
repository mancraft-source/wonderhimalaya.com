<?php

namespace Database\Factories;

use App\Models\TeamMember;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<TeamMember>
 */
class TeamMemberFactory extends Factory
{
    protected $model = TeamMember::class;

    public function definition(): array
    {
        $name = fake()->unique()->name();

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'role' => fake()->randomElement(['Trekking guide', 'Operations manager', 'Mountaineering guide', 'Travel consultant']),
            'excerpt' => fake()->sentence(14),
            'bio' => '<p>'.implode('</p><p>', fake()->paragraphs(2)).'</p>',
            'experience_years' => fake()->numberBetween(2, 25),
            'languages' => fake()->randomElements(['English', 'Nepali', 'Hindi', 'French', 'German'], 2),
            'certifications' => ['NMA Trekking Guide Licence'],
            'social' => [],
            'is_guide' => false,
            'is_published' => true,
            'sort' => 0,
        ];
    }

    public function guide(): static
    {
        return $this->state(['is_guide' => true, 'role' => 'Trekking guide']);
    }

    public function unpublished(): static
    {
        return $this->state(['is_published' => false]);
    }
}
