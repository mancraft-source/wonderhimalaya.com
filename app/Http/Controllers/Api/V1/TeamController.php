<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\V1\TeamMemberResource;
use App\Models\TeamMember;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @tags Team
 */
class TeamController extends ApiController
{
    /** Published team members. Optional: `guides=1`. */
    public function index(Request $request): AnonymousResourceCollection
    {
        return TeamMemberResource::collection(
            TeamMember::query()
                ->where('is_published', true)
                ->when($request->boolean('guides'), fn ($q) => $q->where('is_guide', true))
                ->with('media')
                ->orderBy('sort')
                ->orderBy('name')
                ->get()
        );
    }
}
