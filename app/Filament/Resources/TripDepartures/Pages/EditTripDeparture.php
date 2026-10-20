<?php

namespace App\Filament\Resources\TripDepartures\Pages;

use App\Filament\Resources\TripDepartures\TripDepartureResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTripDeparture extends EditRecord
{
    protected static string $resource = TripDepartureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
