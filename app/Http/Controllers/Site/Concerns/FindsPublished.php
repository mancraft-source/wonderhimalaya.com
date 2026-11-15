<?php

namespace App\Http\Controllers\Site\Concerns;

use App\Support\Preview;
use Illuminate\Database\Eloquent\Builder;

trait FindsPublished
{
    /** Staff who can view the record's admin section can preview drafts; a signed preview link grants its one record; everyone else sees published only. */
    protected function scopeVisible(Builder $query): Builder
    {
        if (($previewKey = Preview::grantedKeyFor($query->getModel())) !== null) {
            return $query->whereKey($previewKey);
        }

        $user = auth()->user();

        if ($user && $user->can('viewAny', $query->getModel()::class)) {
            return $query;
        }

        return $query->published();
    }
}
