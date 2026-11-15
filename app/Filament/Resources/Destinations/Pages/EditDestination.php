<?php

namespace App\Filament\Resources\Destinations\Pages;

use App\Filament\Resources\Destinations\DestinationResource;
use App\Filament\Support\SiteLinkActions;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDestination extends EditRecord
{
    protected static string $resource = DestinationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ...SiteLinkActions::header(),
            DeleteAction::make(),
        ];
    }
}
