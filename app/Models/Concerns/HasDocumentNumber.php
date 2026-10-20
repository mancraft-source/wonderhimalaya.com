<?php

namespace App\Models\Concerns;

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Str;

/**
 * Human-friendly sequential numbers (e.g. Q-2610-0007) plus an unguessable
 * UUID used in public, signed links.
 */
trait HasDocumentNumber
{
    abstract protected static function numberPrefix(): string;

    public static function bootHasDocumentNumber(): void
    {
        static::creating(function (self $model) {
            $model->uuid ??= (string) Str::uuid();
            $model->number ??= static::nextNumber();
        });
    }

    /** Retry with a fresh number if two records grab the same one concurrently. */
    public function save(array $options = []): bool
    {
        for ($attempt = 1; ; $attempt++) {
            try {
                return parent::save($options);
            } catch (UniqueConstraintViolationException $e) {
                if ($this->exists || $attempt >= 3 || ! str_contains($e->getMessage(), 'number')) {
                    throw $e;
                }
                $this->number = static::nextNumber();
            }
        }
    }

    public static function nextNumber(): string
    {
        $prefix = static::numberPrefix().'-'.now()->format('ym').'-';
        $query = static::query()->where('number', 'like', $prefix.'%');

        if (method_exists(static::class, 'bootSoftDeletes')) {
            $query->withTrashed();
        }

        $last = $query->max('number');
        $n = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $n, 4, '0', STR_PAD_LEFT);
    }
}
