<?php

namespace App\Support;

use Spatie\MediaLibrary\MediaCollections\Models\Media;

/** Resolves a design image from the media library by file name (placeholders until new photography arrives). */
final class BrandImage
{
    public static function url(string $fileName, ?string $fallback = null, string $conversion = 'wide'): ?string
    {
        $media = once(fn () => Media::where('file_name', $fileName)->first());

        if (! $media) {
            return $fallback;
        }

        return $media->hasGeneratedConversion($conversion) ? $media->getUrl($conversion) : $media->getUrl();
    }
}
