<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserActions;
use App\Filament\Resources\Users\UserResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

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
}
