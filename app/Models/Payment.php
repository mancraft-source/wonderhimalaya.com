<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Payment extends Model
{
    use LogsActivity;

    protected $guarded = ['id'];

    protected $hidden = ['gateway_payload'];

    protected function casts(): array
    {
        return [
            'status' => PaymentStatus::class,
            'type' => PaymentType::class,
            'amount' => 'decimal:2',
            'gateway_payload' => 'array',
            'expires_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(fn (self $payment) => $payment->uuid ??= (string) Str::uuid());
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnlyDirty()->logOnly(['status', 'amount', 'gateway', 'gateway_reference', 'paid_at']);
    }

    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function isPayable(): bool
    {
        return $this->status === PaymentStatus::Pending && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
