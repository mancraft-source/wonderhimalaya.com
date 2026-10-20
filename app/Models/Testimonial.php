<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Testimonial extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['reviewed_at' => 'date', 'is_featured' => 'boolean', 'is_published' => 'boolean'];
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }
}
