<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Contact extends Model
{
    use LogsActivity, SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['marketing_consent' => 'boolean', 'consent_at' => 'datetime'];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnlyDirty()->logOnly(['first_name', 'last_name', 'email', 'phone', 'owner_id', 'marketing_consent']);
    }

    protected function name(): Attribute
    {
        return Attribute::get(fn () => trim($this->first_name.' '.$this->last_name));
    }

    protected function email(): Attribute
    {
        return Attribute::set(fn (?string $value) => $value ? strtolower(trim($value)) : $value);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class)->latest();
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class)->latest();
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class)->latest();
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest();
    }

    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'notable')->latest();
    }
}
