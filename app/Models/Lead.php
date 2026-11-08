<?php

namespace App\Models;

use App\Enums\LeadStatus;
use App\Enums\LeadType;
use App\Notifications\StaffAlerts;
use App\Support\Notifications\StaffNotifier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\UniqueConstraintViolationException;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Lead extends Model
{
    use LogsActivity, SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'type' => LeadType::class,
            'status' => LeadStatus::class,
            'destinations' => 'array',
            'interests' => 'array',
            'utm' => 'array',
            'start_date' => 'date',
            'end_date' => 'date',
            'estimated_value' => 'decimal:2',
            'next_follow_up_at' => 'datetime',
            'converted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $lead) {
            $lead->reference ??= self::nextReference();
        });

        // Tell the new owner (unless they assigned the lead to themselves).
        static::updated(function (self $lead): void {
            if ($lead->wasChanged('owner_id') && $lead->owner_id && $lead->owner_id !== auth()->id() && ($owner = $lead->owner)) {
                StaffNotifier::send(StaffAlerts::leadAssigned($lead), $owner);
            }
        });
    }

    /** Retry with a fresh reference if two enquiries arrive at the same instant. */
    public function save(array $options = []): bool
    {
        for ($attempt = 1; ; $attempt++) {
            try {
                return parent::save($options);
            } catch (UniqueConstraintViolationException $e) {
                if ($this->exists || $attempt >= 3 || ! str_contains($e->getMessage(), 'reference')) {
                    throw $e;
                }
                $this->reference = self::nextReference();
            }
        }
    }

    public static function nextReference(): string
    {
        $prefix = 'WH-'.now()->format('ym').'-';
        $last = self::withTrashed()->where('reference', 'like', $prefix.'%')->max('reference');
        $n = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $n, 4, '0', STR_PAD_LEFT);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnlyDirty()->logOnly(['status', 'owner_id', 'estimated_value', 'lost_reason', 'next_follow_up_at']);
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

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class)->latest();
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'notable')->latest();
    }

    public function tasks(): MorphMany
    {
        return $this->morphMany(CrmTask::class, 'taskable')->orderBy('due_at');
    }

    public function travellerCount(): int
    {
        return (int) $this->adults + (int) $this->children;
    }
}
