<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Destination;
use App\Models\Post;
use App\Models\Trip;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @tags Search
 */
class SearchController extends ApiController
{
    /** Combined search over trips, destinations and blog posts (max 20 results). */
    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate(['q' => ['required', 'string', 'min:2', 'max:100']]);
        $term = '%'.TripController::like($data['q']).'%';

        $trips = Trip::query()->published()
            ->where(fn ($q) => $q->where('title', 'like', $term)->orWhere('subtitle', 'like', $term)->orWhere('excerpt', 'like', $term))
            ->orderByDesc('is_featured')->orderBy('sort')->limit(10)->get()
            ->map(fn (Trip $t) => ['type' => 'trip', 'id' => $t->id, 'title' => $t->title, 'slug' => $t->slug,
                'excerpt' => $t->excerpt, 'url' => $t->url(), 'image' => $t->imageUrl('hero', 'thumb')]);

        $destinations = Destination::query()
            ->where(fn ($q) => $q->where('name', 'like', $term)->orWhere('tagline', 'like', $term))
            ->orderBy('sort')->limit(5)->get()
            ->map(fn (Destination $d) => ['type' => 'destination', 'id' => $d->id, 'title' => $d->name, 'slug' => $d->slug,
                'excerpt' => $d->tagline, 'url' => url($d->publicPath()), 'image' => $d->imageUrl('hero', 'thumb')]);

        $posts = Post::query()->published()
            ->where(fn ($q) => $q->where('title', 'like', $term)->orWhere('excerpt', 'like', $term))
            ->orderByDesc('published_at')->limit(10)->get()
            ->map(fn (Post $p) => ['type' => 'post', 'id' => $p->id, 'title' => $p->title, 'slug' => $p->slug,
                'excerpt' => $p->excerpt, 'url' => url($p->publicPath()), 'image' => $p->imageUrl('hero', 'thumb')]);

        $results = $trips->concat($destinations)->concat($posts)->take(20)->values();

        return response()->json(['data' => $results, 'meta' => ['q' => $data['q'], 'total' => $results->count()]]);
    }
}
