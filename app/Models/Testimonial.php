<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Testimonial extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['author_email'];

    protected function casts(): array
    {
        return ['reviewed_at' => 'date', 'travelled_on' => 'date', 'is_featured' => 'boolean', 'is_published' => 'boolean'];
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }
}
