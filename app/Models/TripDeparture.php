<?php

namespace App\Models;

use App\Enums\DepartureStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TripDeparture extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'status' => DepartureStatus::class,
        ];
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function tier(): BelongsTo
    {
        return $this->belongsTo(Tier::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function seatsLeft(): int
    {
        return max(0, $this->seats_total - $this->seats_booked);
    }

    public function isBookable(): bool
    {
        return ! in_array($this->status, [DepartureStatus::SoldOut, DepartureStatus::Cancelled], true)
            && $this->seatsLeft() > 0
            && $this->start_date->isFuture();
    }

    /** Falls back to the trip's advertised price when the departure has none. */
    public function effectivePrice(): ?float
    {
        $price = $this->sale_price ?? $this->price ?? $this->trip?->displayPrice();

        return $price !== null ? (float) $price : null;
    }
}
