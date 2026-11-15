<?php

namespace App\Filament\Resources\Trips\Pages;

use App\Filament\Resources\Trips\Tables\TripsTable;
use App\Filament\Resources\Trips\TripResource;
use App\Filament\Support\SiteLinkActions;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditTrip extends EditRecord
{
    protected static string $resource = TripResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ...SiteLinkActions::header(),
            ActionGroup::make([
                TripsTable::replicateAction(),
                DeleteAction::make(),
                ForceDeleteAction::make(),
                RestoreAction::make(),
            ]),
        ];
    }
}
