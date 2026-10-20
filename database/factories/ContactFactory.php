<?php

namespace Database\Factories;

use App\Models\Contact;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contact>
 */
class ContactFactory extends Factory
{
    protected $model = Contact::class;

    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->e164PhoneNumber(),
            'whatsapp' => null,
            'country' => fake()->country(),
            'source' => fake()->randomElement(['website', 'google', 'referral', 'tripadvisor']),
            'marketing_consent' => false,
        ];
    }

    public function consented(): static
    {
        return $this->state(['marketing_consent' => true, 'consent_at' => now()]);
    }
}
