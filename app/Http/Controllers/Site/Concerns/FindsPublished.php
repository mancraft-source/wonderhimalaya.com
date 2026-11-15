<?php

namespace App\Http\Controllers\Site\Concerns;

use App\Support\Permissions;
use App\Support\Preview;
use Illuminate\Database\Eloquent\Builder;

trait FindsPublished
{
    /** Staff with content rights can preview drafts; a signed preview link grants its one record; everyone else sees published only. */
    protected function scopeVisible(Builder $query, string $permission = Permissions::CATALOGUE): Builder
    {
        if (($previewKey = Preview::grantedKeyFor($query->getModel())) !== null) {
            return $query->whereKey($previewKey);
        }

        $user = auth()->user();

        if ($user && $user->can($permission)) {
            return $query;
        }

        return $query->published();
    }
}
