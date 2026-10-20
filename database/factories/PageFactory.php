<?php

namespace Database\Factories;

use App\Enums\PublishStatus;
use App\Models\Page;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Page doesn't use HasFactory — use PageFactory::new()->create().
 *
 * @extends Factory<Page>
 */
class PageFactory extends Factory
{
    protected $model = Page::class;

    public function definition(): array
    {
        $title = Str::title(fake()->unique()->words(3, true));

        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'template' => 'default',
            'excerpt' => fake()->sentence(16),
            'blocks' => [
                ['type' => 'hero', 'data' => ['eyebrow' => null, 'heading' => $title, 'subheading' => fake()->sentence(), 'image' => null, 'cta_label' => null, 'cta_url' => null]],
                ['type' => 'rich_text', 'data' => ['content' => '<p>'.implode('</p><p>', fake()->paragraphs(3)).'</p>']],
            ],
            'status' => PublishStatus::Published,
            'published_at' => now()->subDay(),
            'show_in_footer' => false,
            'sort' => 0,
            'seo' => null,
        ];
    }

    public function draft(): static
    {
        return $this->state(['status' => PublishStatus::Draft, 'published_at' => null]);
    }

    public function childOf(Page $parent): static
    {
        return $this->state(['parent_id' => $parent->getKey()]);
    }

    public function template(string $template): static
    {
        return $this->state(['template' => $template]);
    }

    public function inFooter(): static
    {
        return $this->state(['show_in_footer' => true]);
    }

    /** @param  array<int, array{type: string, data: array<string, mixed>}>  $blocks */
    public function withBlocks(array $blocks): static
    {
        return $this->state(['blocks' => $blocks]);
    }
}
