<?php

namespace App\Filament\Support;

use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;

/** Links to a record's admin page that the current user may actually open: edit when allowed, otherwise the read-only view. */
final class RecordLinks
{
    /** @param  class-string<resource>  $resource */
    public static function to(string $resource, ?Model $record): ?string
    {
        if (! $record) {
            return null;
        }

        if ($resource::hasPage('edit') && $resource::canEdit($record)) {
            return $resource::getUrl('edit', ['record' => $record]);
        }

        if ($resource::hasPage('view') && $resource::canView($record)) {
            return $resource::getUrl('view', ['record' => $record]);
        }

        return null;
    }
}
