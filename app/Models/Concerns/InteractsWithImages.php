<?php

namespace App\Models\Concerns;

use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Shared responsive-image conversions for every content model.
 * Images are stored once; WebP renditions are generated on the queue.
 */
trait InteractsWithImages
{
    use InteractsWithMedia;

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')->fit(Fit::Crop, 480, 360)->format('webp')->quality(78)->queued();
        $this->addMediaConversion('card')->fit(Fit::Crop, 960, 720)->format('webp')->quality(80)->queued();
        $this->addMediaConversion('wide')->fit(Fit::Max, 1920, 1280)->format('webp')->quality(80)->queued();
    }

    public function imageUrl(string $collection = 'hero', string $conversion = 'card'): ?string
    {
        $media = $this->getFirstMedia($collection);

        if (! $media) {
            return null;
        }

        return $media->hasGeneratedConversion($conversion) ? $media->getUrl($conversion) : $media->getUrl();
    }
}
