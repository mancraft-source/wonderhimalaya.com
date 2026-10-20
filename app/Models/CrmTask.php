<?php

namespace App\Models;

use App\Enums\TaskType;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CrmTask extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['type' => TaskType::class, 'due_at' => 'datetime', 'completed_at' => 'datetime'];
    }

    public function taskable(): MorphTo
    {
        return $this->morphTo();
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    #[Scope]
    protected function open(Builder $query): void
    {
        $query->whereNull('completed_at');
    }

    public function isOverdue(): bool
    {
        return ! $this->completed_at && $this->due_at?->isPast();
    }
}
