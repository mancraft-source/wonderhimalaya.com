<?php

namespace Database\Factories;

use App\Models\Menu;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * `location` is unique — pass one explicitly when creating several menus.
 *
 * @extends Factory<Menu>
 */
class MenuFactory extends Factory
{
    protected $model = Menu::class;

    public function definition(): array
    {
        return [
            'name' => 'Header',
            'location' => 'header',
            'items' => [
                $this->item('Trekking', '/trekking', [
                    $this->item('Everest region', '/destinations/everest'),
                    $this->item('Annapurna region', '/destinations/annapurna'),
                ]),
                $this->item('Tours', '/tours'),
                $this->item('Blog', '/blog'),
                $this->item('Contact', '/contact'),
            ],
        ];
    }

    public function location(string $location): static
    {
        return $this->state(['location' => $location, 'name' => Str::headline($location)]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $children
     * @return array<string, mixed>
     */
    public function item(string $label, string $url, array $children = [], bool $newTab = false): array
    {
        return ['label' => $label, 'url' => $url, 'open_new_tab' => $newTab, 'children' => $children];
    }
}
