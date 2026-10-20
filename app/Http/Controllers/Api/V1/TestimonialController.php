<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\V1\TestimonialResource;
use App\Models\Testimonial;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @tags Testimonials
 */
class TestimonialController extends ApiController
{
    /** Published reviews. Optional: `featured=1`, `trip` (slug). */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Testimonial::query()
            ->where('is_published', true)
            ->with('trip')
            ->when($request->boolean('featured'), fn ($q) => $q->where('is_featured', true))
            ->when($request->filled('trip'), fn ($q) => $q->whereHas('trip', fn ($t) => $t->published()->where('slug', $request->string('trip'))))
            ->orderByDesc('is_featured')
            ->orderByDesc('reviewed_at')
            ->orderByDesc('id');

        return TestimonialResource::collection($query->paginate($this->perPage($request, 20))->withQueryString());
    }
}
