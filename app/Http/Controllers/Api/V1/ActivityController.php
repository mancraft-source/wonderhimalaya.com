<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\V1\ActivityResource;
use App\Models\Activity;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @tags Activities
 */
class ActivityController extends ApiController
{
    public function index(): AnonymousResourceCollection
    {
        return ActivityResource::collection(
            Activity::query()->withCount(['trips' => fn ($q) => $q->published()])->with('media')->orderBy('sort')->orderBy('name')->get()
        );
    }

    public function show(string $slug): ActivityResource
    {
        $activity = Activity::query()->where('slug', $slug)
            ->withCount(['trips' => fn ($q) => $q->published()])
            ->with('media')
            ->firstOrFail();

        return (new ActivityResource($activity))->detailed();
    }
}
