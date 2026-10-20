<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Passport, insurance and medical fields are encrypted at rest. */
class Traveller extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['passport_number', 'passport_expiry', 'date_of_birth', 'insurance_policy', 'medical_notes'];

    protected function casts(): array
    {
        return [
            'is_lead' => 'boolean',
            'date_of_birth' => 'encrypted',
            'passport_number' => 'encrypted',
            'passport_expiry' => 'encrypted',
            'insurance_provider' => 'encrypted',
            'insurance_policy' => 'encrypted',
            'medical_notes' => 'encrypted',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
