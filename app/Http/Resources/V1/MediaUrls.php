<?php

namespace App\Http\Resources\V1;

use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/** Image URLs with conversions (thumb/card/wide), falling back to the original while conversions are queued. */
final class MediaUrls
{
    public const CONVERSIONS = ['thumb', 'card', 'wide'];

    /** @return array<string, mixed>|null */
    public static function first(HasMedia $model, string $collection): ?array
    {
        $media = $model->getFirstMedia($collection);

        return $media ? self::media($media) : null;
    }

    /** @return array<int, array<string, mixed>> */
    public static function all(HasMedia $model, string $collection): array
    {
        return $model->getMedia($collection)->map(fn (Media $media) => self::media($media))->values()->all();
    }

    /** @return array<string, mixed> */
    public static function media(Media $media): array
    {
        $urls = ['original' => $media->getUrl()];

        foreach (self::CONVERSIONS as $conversion) {
            $urls[$conversion] = $media->hasGeneratedConversion($conversion) ? $media->getUrl($conversion) : $urls['original'];
        }

        return [
            'id' => $media->id,
            'alt' => $media->getCustomProperty('alt') ?? $media->name,
            'caption' => $media->getCustomProperty('caption'),
            'width' => $media->getCustomProperty('width'),
            'height' => $media->getCustomProperty('height'),
            'urls' => $urls,
        ];
    }
}
