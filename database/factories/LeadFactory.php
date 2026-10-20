<?php

namespace Database\Factories;

use App\Enums\LeadStatus;
use App\Enums\LeadType;
use App\Models\Lead;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lead>
 */
class LeadFactory extends Factory
{
    protected $model = Lead::class;

    public function definition(): array
    {
        return [
            'type' => LeadType::TripEnquiry,
            'status' => LeadStatus::New,
            'contact_id' => ContactFactory::new(),
            'trip_id' => null,
            'adults' => fake()->numberBetween(1, 4),
            'children' => 0,
            'travel_month' => now()->addMonths(3)->format('Y-m'),
            'start_date' => null,
            'estimated_value' => fake()->randomElement([null, 1500, 3000, 4500]),
            'currency' => 'USD',
            'message' => fake()->paragraph(),
            'source' => fake()->randomElement(['website', 'google', 'referral', 'whatsapp']),
            'utm' => null,
        ];
    }

    public function status(LeadStatus $status): static
    {
        return $this->state(['status' => $status]);
    }

    public function ownedBy(?User $user): static
    {
        return $this->state(['owner_id' => $user?->id]);
    }

    public function forTrip(?Trip $trip = null): static
    {
        return $this->state(['trip_id' => $trip?->id ?? TripFactory::new(), 'type' => LeadType::TripEnquiry]);
    }

    public function tailorMade(): static
    {
        return $this->state(['type' => LeadType::TailorMade, 'trip_id' => null]);
    }
}
