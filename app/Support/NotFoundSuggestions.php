<?php

namespace App\Support;

use App\Models\Trip;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

/** "Were you looking for…?" on the 404 page: published trips whose titles share words with the broken URL. */
final class NotFoundSuggestions
{
    private const STOP_WORDS = ['trip', 'trips', 'tour', 'tours', 'trek', 'treks', 'trekking', 'nepal', 'days', 'day', 'with', 'from', 'the', 'and', 'html', 'page', 'blog', 'activities', 'destination'];

    /** @return array<int, string> */
    public static function keywords(string $path): array
    {
        return collect(preg_split('/[^a-z0-9]+/', Str::lower(rawurldecode($path)), -1, PREG_SPLIT_NO_EMPTY))
            ->filter(fn (string $word) => strlen($word) > 3 && ! ctype_digit($word) && ! in_array($word, self::STOP_WORDS, true))
            ->unique()->take(6)->values()->all();
    }

    /** @return Collection<int, Trip> */
    public static function trips(string $path, int $limit = 3): Collection
    {
        try {
            $keywords = self::keywords($path);
            $query = Trip::published()->with(['destination', 'difficulty', 'media']);

            if ($keywords === []) {
                return $query->orderByDesc('is_featured')->orderByDesc('is_popular')->orderBy('sort')->limit($limit)->get();
            }

            $matches = (clone $query)
                ->where(fn ($q) => collect($keywords)->each(fn ($word) => $q->orWhere('title', 'like', "%{$word}%")->orWhere('slug', 'like', "%{$word}%")))
                ->limit(40)->get()
                ->sortByDesc(fn (Trip $trip) => collect($keywords)->filter(fn ($word) => str_contains(Str::lower($trip->title.' '.$trip->slug), $word))->count())
                ->take($limit)->values();

            return $matches->isNotEmpty() ? new Collection($matches->all()) : $query->orderByDesc('is_featured')->orderByDesc('is_popular')->orderBy('sort')->limit($limit)->get();
        } catch (\Throwable) {
            return new Collection;
        }
    }
}
