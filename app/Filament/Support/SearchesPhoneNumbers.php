<?php

namespace App\Filament\Support;

use Illuminate\Database\Eloquent\Builder;

/**
 * For resources: a global search that looks like a phone number also matches the
 * phone columns with punctuation ignored, so "+977 9812 345678" finds "9779812345678".
 */
trait SearchesPhoneNumbers
{
    /** @return array<int, string> Columns on the model, or "relation.column". */
    abstract protected static function getGlobalSearchPhoneColumns(): array;

    protected static function applyGlobalSearchAttributeConstraints(Builder $query, string $search): void
    {
        $digits = GlobalSearch::phoneDigits($search);

        if ($digits === null) {
            parent::applyGlobalSearchAttributeConstraints($query, $search);

            return;
        }

        $query->where(function (Builder $query) use ($search, $digits): void {
            $query->where(fn (Builder $query) => parent::applyGlobalSearchAttributeConstraints($query, $search))
                ->orWhere(fn (Builder $query): Builder => GlobalSearch::wherePhoneMatches($query, static::getGlobalSearchPhoneColumns(), $digits));
        });
    }
}
