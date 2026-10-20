<?php

namespace App\Models\Concerns;

use App\Models\Redirect;

/**
 * When a public URL changes (slug edit), store a 301 from the old path
 * so links and search rankings survive renames made in the admin.
 */
trait RecordsSlugRedirects
{
    abstract public function publicPath(): string;

    public static function bootRecordsSlugRedirects(): void
    {
        static::updating(function (self $model) {
            if (! $model->isDirty('slug') && ! $model->isDirty('path')) {
                return;
            }

            $old = (clone $model)->setRawAttributes($model->getOriginal())->publicPath();
            $new = $model->publicPath();

            if ($old !== $new) {
                Redirect::point($old, $new);
            }
        });
    }
}
