<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Menu extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['items' => 'array'];
    }

    protected static function booted(): void
    {
        static::saved(fn (self $menu) => Cache::forget('menu.'.$menu->location));
        static::deleted(fn (self $menu) => Cache::forget('menu.'.$menu->location));
    }

    /** @return array<int, array<string, mixed>> */
    public static function itemsFor(string $location): array
    {
        return Cache::rememberForever('menu.'.$location, fn () => self::where('location', $location)->first()?->items ?? []);
    }
}
