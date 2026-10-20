<?php

namespace App\Http\Resources\V1;

use Illuminate\Database\Eloquent\Model;

final class SeoData
{
    /** @return array<string, mixed> */
    public static function for(Model $model): array
    {
        $seo = (array) ($model->seo ?? []);

        return [
            'title' => method_exists($model, 'seoTitle') ? $model->seoTitle() : ($seo['title'] ?? null),
            'description' => method_exists($model, 'seoDescription') ? $model->seoDescription() : ($seo['description'] ?? null),
            'canonical' => $seo['canonical'] ?? (method_exists($model, 'publicPath') ? url($model->publicPath()) : null),
            'og_image' => $seo['og_image'] ?? null,
            'noindex' => (bool) ($seo['noindex'] ?? false),
        ];
    }
}
