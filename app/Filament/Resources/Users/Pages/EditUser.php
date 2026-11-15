<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserActions;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use App\Support\AccessControl;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    /** @var array<int, string> Role names before this save, for the activity log. */
    private array $rolesBeforeSave = [];

    protected function getHeaderActions(): array
    {
        return [
            UserActions::createToken(),
            UserActions::revokeTokens(),
            DeleteAction::make()->hidden(fn (): bool => $this->getRecord()->is(auth()->user())),
        ];
    }

    public function apiTokenCreatedAction(): Action
    {
        return UserActions::tokenCreated();
    }

    /** Keep at least one active admin, and never let someone hand out a role beyond their own access. */
    protected function beforeSave(): void
    {
        /** @var User $record */
        $record = $this->getRecord();
        /** @var User $actor */
        $actor = auth()->user();
        $roleIds = array_map('intval', array_filter((array) ($this->data['roles'] ?? [])));
        $currentRoleIds = $record->roles()->pluck('id')->all();

        if (AccessControl::wouldRemoveLastAdmin($record, $roleIds, (bool) ($this->data['is_active'] ?? false))) {
            Notification::make()
                ->danger()
                ->title('At least one active admin is required')
                ->body('This is the last active administrator. Give someone else the admin role before removing it or deactivating this account.')
                ->send();

            $this->halt();
        }

        if (! AccessControl::canAssignRoles($actor, array_values(array_diff($roleIds, $currentRoleIds)))) {
            Notification::make()
                ->danger()
                ->title('You cannot assign that role')
                ->body('You can only assign roles whose permissions you have yourself.')
                ->send();

            $this->halt();
        }

        $this->rolesBeforeSave = $record->roles()->pluck('name')->all();
    }

    protected function afterSave(): void
    {
        /** @var User $record */
        $record = $this->getRecord();

        AccessControl::logUserRoles($record, $this->rolesBeforeSave, $record->roles()->pluck('name')->all(), auth()->user());
    }
}
