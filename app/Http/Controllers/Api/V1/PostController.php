<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\V1\PostResource;
use App\Models\Post;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * @tags Blog
 */
class PostController extends ApiController
{
    /**
     * Published blog posts. Filters: `filter[category]` (slug), `filter[tag]` (slug), `filter[search]`.
     * Sort: `published_at` / `-published_at` (default), `title`.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $posts = QueryBuilder::for(Post::query()->published()->with(['media', 'category', 'author']))
            ->allowedFilters(
                AllowedFilter::callback('category', fn (Builder $q, $value) => $q->whereHas('category', fn ($c) => $c->whereIn('slug', (array) $value))),
                AllowedFilter::callback('tag', fn (Builder $q, $value) => $q->whereHas('tags', fn ($t) => $t->whereIn('slug', (array) $value))),
                AllowedFilter::callback('search', fn (Builder $q, $value) => $q->where(function (Builder $w) use ($value) {
                    $term = '%'.TripController::like(implode(' ', (array) $value)).'%';
                    $w->where('title', 'like', $term)->orWhere('excerpt', 'like', $term);
                })),
            )
            ->allowedSorts('published_at', 'title')
            ->defaultSort('-published_at')
            ->paginate($this->perPage($request))
            ->withQueryString();

        return PostResource::collection($posts);
    }

    public function show(string $slug): PostResource
    {
        $post = Post::query()->published()->where('slug', $slug)
            ->with(['media', 'category', 'tags', 'author'])
            ->firstOrFail();

        return (new PostResource($post))->detailed();
    }
}
