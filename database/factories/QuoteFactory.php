<?php

namespace Database\Factories;

use App\Enums\QuoteStatus;
use App\Models\Quote;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Quote>
 */
class QuoteFactory extends Factory
{
    protected $model = Quote::class;

    public function definition(): array
    {
        return [
            'contact_id' => ContactFactory::new(),
            'title' => fake()->randomElement(['Everest Base Camp Trek', 'Tailor-made trip']),
            'status' => QuoteStatus::Draft,
            'pax' => 2,
            'currency' => 'USD',
            'discount' => 0,
            'tax' => 0,
            'deposit_percent' => 20,
            'valid_until' => now()->addDays(14),
        ];
    }

    /** Adds line items and recalculates totals. */
    public function withItems(int $count = 1, float $unitPrice = 1000, float $quantity = 2): static
    {
        return $this->afterCreating(function (Quote $quote) use ($count, $unitPrice, $quantity) {
            for ($i = 0; $i < $count; $i++) {
                $quote->items()->create(['description' => 'Line item '.($i + 1), 'quantity' => $quantity, 'unit_price' => $unitPrice, 'sort' => $i]);
            }
            $quote->unsetRelation('items');
            $quote->recalculate();
            $quote->save();
        });
    }

    public function sent(): static
    {
        return $this->state(['status' => QuoteStatus::Sent, 'sent_at' => now()]);
    }
}
