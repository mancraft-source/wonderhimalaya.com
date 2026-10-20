<?php

namespace Database\Factories;

use App\Enums\PublishStatus;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\TeamMember;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Post doesn't use HasFactory — use PostFactory::new()->create().
 * Category and author are left null unless requested via the states below.
 *
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    protected $model = Post::class;

    public function definition(): array
    {
        $title = rtrim(fake()->unique()->sentence(6), '.');

        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'excerpt' => fake()->sentence(20),
            'body' => '<p>'.implode('</p><p>', fake()->paragraphs(6)).'</p>',
            'is_featured' => false,
            'status' => PublishStatus::Published,
            'published_at' => fake()->dateTimeBetween('-1 year', '-1 day'),
            'seo' => null,
        ];
    }

    public function draft(): static
    {
        return $this->state(['status' => PublishStatus::Draft, 'published_at' => null]);
    }

    public function scheduled(): static
    {
        return $this->state(['status' => PublishStatus::Published, 'published_at' => now()->addWeek()]);
    }

    public function featured(): static
    {
        return $this->state(['is_featured' => true]);
    }

    public function inCategory(?PostCategory $category = null): static
    {
        return $this->state(fn () => ['post_category_id' => ($category ?? PostCategoryFactory::new()->create())->getKey()]);
    }

    public function byAuthor(?TeamMember $author = null): static
    {
        return $this->state(fn () => ['team_member_id' => ($author ?? TeamMemberFactory::new()->create())->getKey()]);
    }
}
