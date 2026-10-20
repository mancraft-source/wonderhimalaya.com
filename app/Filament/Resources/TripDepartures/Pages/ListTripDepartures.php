<?php

namespace App\Filament\Resources\TripDepartures\Pages;

use App\Filament\Resources\TripDepartures\TripDepartureResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTripDepartures extends ListRecords
{
    protected static string $resource = TripDepartureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
