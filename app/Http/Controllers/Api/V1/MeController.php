<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\V1\UserResource;
use Illuminate\Http\Request;

/**
 * @tags Account
 */
class MeController extends ApiController
{
    /** The token owner, their roles/permissions and the token's abilities. */
    public function __invoke(Request $request): UserResource
    {
        return new UserResource($request->user());
    }
}
