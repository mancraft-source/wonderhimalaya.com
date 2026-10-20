<?php

namespace Database\Factories;

use App\Models\QuoteItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuoteItem>
 */
class QuoteItemFactory extends Factory
{
    protected $model = QuoteItem::class;

    public function definition(): array
    {
        return [
            'quote_id' => QuoteFactory::new(),
            'description' => fake()->sentence(3),
            'quantity' => 1,
            'unit_price' => fake()->randomElement([100, 250, 1000]),
            'sort' => 0,
        ];
    }
}
