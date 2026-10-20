<?php

namespace App\Models;

use App\Enums\QuoteStatus;
use App\Models\Concerns\HasDocumentNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Quote extends Model
{
    use HasDocumentNumber, LogsActivity, SoftDeletes;

    protected $guarded = ['id'];

    protected static function numberPrefix(): string
    {
        return 'Q';
    }

    protected function casts(): array
    {
        return [
            'status' => QuoteStatus::class,
            'start_date' => 'date',
            'end_date' => 'date',
            'valid_until' => 'date',
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'tax' => 'decimal:2',
            'total' => 'decimal:2',
            'deposit_amount' => 'decimal:2',
            'sent_at' => 'datetime',
            'viewed_at' => 'datetime',
            'accepted_at' => 'datetime',
            'declined_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnlyDirty()->logOnly(['status', 'total', 'deposit_amount', 'valid_until']);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function tier(): BelongsTo
    {
        return $this->belongsTo(Tier::class);
    }

    public function departure(): BelongsTo
    {
        return $this->belongsTo(TripDeparture::class, 'trip_departure_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuoteItem::class)->orderBy('sort');
    }

    public function booking(): HasOne
    {
        return $this->hasOne(Booking::class);
    }

    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable');
    }

    /** Recalculate totals from line items; amounts are never trusted from input. */
    public function recalculate(): void
    {
        $this->loadMissing('items');

        $subtotal = $this->items->sum(fn (QuoteItem $item) => round((float) $item->quantity * (float) $item->unit_price, 2));
        $total = max(0, $subtotal - (float) $this->discount + (float) $this->tax);

        $this->subtotal = $subtotal;
        $this->total = $total;
        $percent = $this->deposit_percent ?? (int) Setting::get('booking.deposit_percent', 20);
        $this->deposit_amount = round($total * ($percent / 100), 2);
    }

    public function isExpired(): bool
    {
        return $this->valid_until !== null && $this->valid_until->endOfDay()->isPast();
    }

    public function isAcceptable(): bool
    {
        return in_array($this->status, [QuoteStatus::Sent, QuoteStatus::Viewed], true) && ! $this->isExpired();
    }
}
