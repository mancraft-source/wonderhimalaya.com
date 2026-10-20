<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Redirect extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'last_hit_at' => 'datetime'];
    }

    public static function normalize(string $path): string
    {
        $path = parse_url($path, PHP_URL_PATH) ?: '/';

        return '/'.trim(strtolower(rawurldecode($path)), '/');
    }

    /**
     * Create or repoint a redirect, collapsing chains (A→B, B→C becomes A→C, B→C)
     * and removing loops when content moves back to an old URL.
     */
    public static function point(string $from, string $to, int $status = 301): void
    {
        $from = self::normalize($from);
        $to = str_starts_with($to, 'http') ? $to : self::normalize($to);

        if ($from === $to) {
            return;
        }

        self::where('from_path', $to)->delete();
        self::where('to_path', $from)->update(['to_path' => $to]);
        self::updateOrCreate(['from_path' => $from], ['to_path' => $to, 'status_code' => $status, 'is_active' => true]);
    }
}
