<?php

namespace Database\Factories;

use App\Enums\TaskType;
use App\Models\CrmTask;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CrmTask>
 */
class CrmTaskFactory extends Factory
{
    protected $model = CrmTask::class;

    public function definition(): array
    {
        return [
            'type' => TaskType::Todo,
            'title' => fake()->sentence(4),
            'description' => null,
            'due_at' => fake()->dateTimeBetween('-2 days', '+1 week'),
            'completed_at' => null,
        ];
    }

    public function overdue(): static
    {
        return $this->state(['due_at' => now()->subDay(), 'completed_at' => null]);
    }

    public function completed(): static
    {
        return $this->state(['completed_at' => now()]);
    }

    public function assignedTo(User $user): static
    {
        return $this->state(['assignee_id' => $user->id]);
    }

    public function forLead(Lead $lead): static
    {
        return $this->state(['taskable_type' => 'lead', 'taskable_id' => $lead->id]);
    }
}
