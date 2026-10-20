<?php

namespace App\Policies;

use App\Models\Lead;
use App\Models\User;
use App\Support\Permissions;
use Illuminate\Database\Eloquent\Model;

/** Sales staff see unassigned leads and their own; managers see everything. */
class LeadPolicy extends PermissionPolicy
{
    protected function permission(): string
    {
        return Permissions::CRM_MANAGE;
    }

    protected function viewPermission(): string
    {
        return Permissions::CRM_VIEW;
    }

    public function view(User $user, Model $model): bool
    {
        /** @var Lead $model */
        return $this->viewAny($user) && $this->canSee($user, $model);
    }

    public function update(User $user, Model $model): bool
    {
        /** @var Lead $model */
        return $user->can($this->permission()) && $this->canSee($user, $model);
    }

    public function delete(User $user, Model $model): bool
    {
        return $user->can(Permissions::CRM_VIEW_ALL) && $user->can($this->permission());
    }

    private function canSee(User $user, Lead $lead): bool
    {
        return $user->can(Permissions::CRM_VIEW_ALL) || $lead->owner_id === null || $lead->owner_id === $user->id;
    }
}
