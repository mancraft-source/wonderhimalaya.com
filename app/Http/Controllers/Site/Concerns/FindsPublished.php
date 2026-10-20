<?php

namespace App\Http\Controllers\Site\Concerns;

use App\Support\Permissions;
use Illuminate\Database\Eloquent\Builder;

trait FindsPublished
{
    /** Staff with content rights can preview drafts; everyone else sees published only. */
    protected function scopeVisible(Builder $query, string $permission = Permissions::CATALOGUE): Builder
    {
        $user = auth()->user();

        if ($user && $user->can($permission)) {
            return $query;
        }

        return $query->published();
    }
}
