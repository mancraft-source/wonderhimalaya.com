<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use App\Support\AccessControl;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    /** Never let someone hand out a role beyond their own access. */
    protected function beforeCreate(): void
    {
        /** @var User $actor */
        $actor = auth()->user();
        $roleIds = array_map('intval', array_filter((array) ($this->data['roles'] ?? [])));

        if (! AccessControl::canAssignRoles($actor, $roleIds)) {
            Notification::make()
                ->danger()
                ->title('You cannot assign that role')
                ->body('You can only assign roles whose permissions you have yourself.')
                ->send();

            $this->halt();
        }
    }

    protected function afterCreate(): void
    {
        /** @var User $record */
        $record = $this->getRecord();

        AccessControl::logUserRoles($record, [], $record->roles()->pluck('name')->all(), auth()->user());
    }
}
