<?php

namespace Database\Factories;

use App\Models\Note;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Note>
 */
class NoteFactory extends Factory
{
    protected $model = Note::class;

    public function definition(): array
    {
        return [
            'notable_type' => 'lead',
            'notable_id' => LeadFactory::new(),
            'body' => fake()->paragraph(),
        ];
    }
}
