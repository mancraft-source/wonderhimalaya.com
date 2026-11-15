<?php

namespace App\Policies;

use App\Models\Lead;
use App\Models\User;
use App\Support\Permissions;
use Illuminate\Database\Eloquent\Model;

/** Staff without leads.view_all see unassigned leads and their own; with it, everything. */
class LeadPolicy extends PermissionPolicy
{
    protected function section(): string
    {
        return 'leads';
    }

    public function view(User $user, Model $model): bool
    {
        /** @var Lead $model */
        return $this->viewAny($user) && $this->canSee($user, $model);
    }

    public function update(User $user, Model $model): bool
    {
        /** @var Lead $model */
        return parent::update($user, $model) && $this->canSee($user, $model);
    }

    public function delete(User $user, Model $model): bool
    {
        /** @var Lead $model */
        return parent::delete($user, $model) && $this->canSee($user, $model);
    }

    public function restore(User $user, Model $model): bool
    {
        /** @var Lead $model */
        return parent::restore($user, $model) && $this->canSee($user, $model);
    }

    private function canSee(User $user, Lead $lead): bool
    {
        return $user->can(Permissions::LEADS_VIEW_ALL) || $lead->owner_id === null || $lead->owner_id === $user->id;
    }
}
