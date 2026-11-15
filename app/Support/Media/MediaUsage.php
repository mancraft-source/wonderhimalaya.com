<?php

namespace App\Support\Media;

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Throwable;

/**
 * Where a media-library file is used (its owner record), plus the alt text the public site reads.
 * A file counts as used while its owner exists, including owners in the trash (they can be restored).
 */
final class MediaUsage
{
    /** Files above this size are flagged as "large" (1 MB). */
    public const LARGE_BYTES = 1_048_576;

    /** Custom property the site reads for alt text (components/site/picture.blade.php, the API's MediaUrls). */
    public const ALT_PROPERTY = 'alt';

    public static function owner(Media $media): ?Model
    {
        if ($media->model) {
            return $media->model;
        }

        $class = self::ownerClass($media->model_type);

        if ($class === null || ! self::softDeletes($class)) {
            return null;
        }

        return $class::query()->withTrashed()->find($media->model_id);
    }

    public static function isUsed(Media $media): bool
    {
        return self::owner($media) !== null;
    }

    public static function ownerTitle(Model $owner): string
    {
        $attributes = $owner->getAttributes();

        return (string) ($attributes['title'] ?? $attributes['name'] ?? '#'.$owner->getKey());
    }

    public static function ownerTypeLabel(string $modelType): string
    {
        return Str::headline(class_basename($modelType));
    }

    public static function ownerUrl(Model $owner): ?string
    {
        $resource = Filament::getModelResource($owner);

        if (! $resource) {
            return null;
        }

        try {
            return $resource::hasPage('edit') && $resource::canEdit($owner) ? $resource::getUrl('edit', ['record' => $owner]) : null;
        } catch (Throwable) {
            return null;
        }
    }

    /** The 'thumb' rendition when it has been generated, otherwise the original file. */
    public static function thumbnailUrl(Media $media): string
    {
        return $media->hasGeneratedConversion('thumb') ? $media->getUrl('thumb') : $media->getUrl();
    }

    public static function isLarge(Media $media): bool
    {
        return (int) $media->size > self::LARGE_BYTES;
    }

    public static function alt(Media $media): ?string
    {
        $alt = trim((string) $media->getCustomProperty(self::ALT_PROPERTY, ''));

        return $alt === '' ? null : $alt;
    }

    /** "1920 × 1280" when the dimensions were stored in the custom properties. */
    public static function dimensions(Media $media): ?string
    {
        $width = $media->getCustomProperty('width') ?? $media->getCustomProperty('dimensions.width');
        $height = $media->getCustomProperty('height') ?? $media->getCustomProperty('dimensions.height');

        return $width && $height ? "{$width} × {$height}" : null;
    }

    /**
     * Limits the query to files whose owner record no longer exists (trashed owners still count as existing).
     *
     * @param  Builder<Media>  $query
     * @return Builder<Media>
     */
    public static function whereUnused(Builder $query): Builder
    {
        $mediaTable = $query->getModel()->getTable();
        $types = $query->getModel()->newQuery()->distinct()->pluck('model_type');

        return $query->where(function (Builder $query) use ($types, $mediaTable): void {
            $query->whereRaw('1 = 0');

            foreach ($types as $type) {
                $class = self::ownerClass($type);

                if ($class === null) {
                    $query->orWhere("{$mediaTable}.model_type", $type);

                    continue;
                }

                $owner = new $class;

                $query->orWhere(fn (Builder $query) => $query
                    ->where("{$mediaTable}.model_type", $type)
                    ->whereNotExists(fn (QueryBuilder $owners) => $owners
                        ->selectRaw('1')
                        ->from($owner->getTable())
                        ->whereColumn($owner->getQualifiedKeyName(), "{$mediaTable}.model_id")));
            }
        });
    }

    /**
     * @param  Builder<Media>  $query
     * @return Builder<Media>
     */
    public static function whereMissingAlt(Builder $query): Builder
    {
        $column = 'custom_properties->'.self::ALT_PROPERTY;

        return $query->where(fn (Builder $query) => $query->whereNull($column)->orWhere($column, ''));
    }

    /** @return class-string<Model>|null */
    private static function ownerClass(string $modelType): ?string
    {
        $class = Relation::getMorphedModel($modelType) ?? $modelType;

        return class_exists($class) && is_subclass_of($class, Model::class) ? $class : null;
    }

    /** @param  class-string<Model>  $class */
    private static function softDeletes(string $class): bool
    {
        return in_array(SoftDeletes::class, class_uses_recursive($class), true);
    }
}
