<?php

namespace App\Models\Concerns;

use App\Enums\PublishStatus;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;

trait HasPublishStatus
{
    public function initializeHasPublishStatus(): void
    {
        $this->mergeCasts([
            'status' => PublishStatus::class,
            'published_at' => 'datetime',
        ]);
    }

    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where($this->qualifyColumn('status'), PublishStatus::Published)
            ->where(fn (Builder $q) => $q->whereNull($this->qualifyColumn('published_at'))
                ->orWhere($this->qualifyColumn('published_at'), '<=', now()));
    }

    public function isPublished(): bool
    {
        return $this->status === PublishStatus::Published
            && ($this->published_at === null || $this->published_at->isPast());
    }
}
