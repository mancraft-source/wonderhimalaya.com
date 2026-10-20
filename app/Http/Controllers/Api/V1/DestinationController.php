<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\V1\DestinationResource;
use App\Models\Destination;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @tags Destinations
 */
class DestinationController extends ApiController
{
    /** Destination tree (top-level regions with their children) and published trip counts. */
    public function index(): AnonymousResourceCollection
    {
        $count = ['trips' => fn ($q) => $q->published()];

        $destinations = Destination::query()
            ->whereNull('parent_id')
            ->withCount($count)
            ->with(['media', 'children' => fn ($q) => $q->withCount($count)->with('media', 'children')])
            ->orderBy('sort')
            ->orderBy('name')
            ->get();

        return DestinationResource::collection($destinations);
    }

    public function show(string $slug): DestinationResource
    {
        $destination = Destination::query()
            ->where('slug', $slug)
            ->withCount(['trips' => fn ($q) => $q->published()])
            ->with(['media', 'parent', 'children' => fn ($q) => $q->withCount(['trips' => fn ($t) => $t->published()])->with('media')])
            ->firstOrFail();

        return (new DestinationResource($destination))->detailed();
    }
}
