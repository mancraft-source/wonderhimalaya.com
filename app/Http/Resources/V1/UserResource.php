<?php

namespace App\Http\Resources\V1;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class UserResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $isSelf = $request->user()?->is($this->resource);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            $this->mergeWhen($isSelf, fn () => [
                'roles' => $this->getRoleNames()->values(),
                'permissions' => $this->getAllPermissions()->pluck('name')->values(),
                'token_abilities' => $this->currentAccessToken()?->abilities ?? [],
            ]),
        ];
    }
}
