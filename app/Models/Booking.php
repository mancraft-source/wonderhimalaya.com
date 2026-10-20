<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\Concerns\HasDocumentNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Booking extends Model
{
    use HasDocumentNumber, LogsActivity, SoftDeletes;

    protected $guarded = ['id'];

    protected static function numberPrefix(): string
    {
        return 'WHB';
    }

    protected function casts(): array
    {
        return [
            'status' => BookingStatus::class,
            'start_date' => 'date',
            'end_date' => 'date',
            'balance_due_on' => 'date',
            'total' => 'decimal:2',
            'deposit_amount' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'emergency_contact' => 'encrypted',
            'confirmed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnlyDirty()->logOnly(['status', 'total', 'amount_paid', 'start_date', 'owner_id']);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
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

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function travellers(): HasMany
    {
        return $this->hasMany(Traveller::class);
    }

    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable')->latest();
    }

    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'notable')->latest();
    }

    public function balanceDue(): float
    {
        return max(0, round((float) $this->total - (float) $this->amount_paid, 2));
    }

    /** Re-sum successful payments and move the booking status forward. */
    public function syncPaymentTotals(): void
    {
        $paid = (float) $this->payments()->where('status', PaymentStatus::Paid)->sum('amount');
        $this->amount_paid = $paid;

        if ($this->status === BookingStatus::PendingDeposit && $paid > 0 && $paid >= (float) $this->deposit_amount) {
            $this->status = BookingStatus::Confirmed;
            $this->confirmed_at ??= now();
        }

        if (in_array($this->status, [BookingStatus::Confirmed, BookingStatus::PendingDeposit], true) && $this->total > 0 && $paid >= (float) $this->total) {
            $this->status = BookingStatus::PaidInFull;
            $this->confirmed_at ??= now();
        }

        $this->save();
    }
}
