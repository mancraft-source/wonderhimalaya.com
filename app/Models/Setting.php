<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['value' => 'json'];
    }

    protected static function booted(): void
    {
        static::saved(function (self $setting): void {
            Cache::forget('settings');
            if ($setting->key === 'seo') {
                Cache::forget('sitemap.xml');
            }
        });
        static::deleted(fn () => Cache::forget('settings'));
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $all = Cache::rememberForever('settings', fn () => self::pluck('value', 'key')->all());

        return data_get($all, $key, $default);
    }

    public static function put(string $key, mixed $value): void
    {
        self::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
