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

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }
}
