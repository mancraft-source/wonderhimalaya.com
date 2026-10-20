<?php

namespace Database\Factories;

use App\Models\Traveller;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Traveller>
 */
class TravellerFactory extends Factory
{
    protected $model = Traveller::class;

    public function definition(): array
    {
        return [
            'booking_id' => BookingFactory::new(),
            'is_lead' => false,
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->safeEmail(),
            'nationality' => fake()->country(),
            'passport_number' => strtoupper(fake()->bothify('??#######')),
            'passport_expiry' => now()->addYears(5)->toDateString(),
            'date_of_birth' => fake()->date('Y-m-d', '-20 years'),
        ];
    }
}
