<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteItem extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:2', 'unit_price' => 'decimal:2', 'total' => 'decimal:2'];
    }

    protected static function booted(): void
    {
        static::saving(function (self $item) {
            $item->total = round((float) $item->quantity * (float) $item->unit_price, 2);
        });
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }
}
