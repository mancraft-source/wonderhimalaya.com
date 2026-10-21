<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TripDay extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['meals' => 'array', 'distance_km' => 'decimal:1'];
    }

    /** Walking time without a leading distance (imported values often read "8 Km, 3-4 hrs trek"). */
    public function durationLabel(): ?string
    {
        $label = trim((string) preg_replace('/^\s*[\d.]+\s*km\b[,;\s]*/i', '', (string) $this->duration));

        return $label !== '' ? $label : null;
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }
}
