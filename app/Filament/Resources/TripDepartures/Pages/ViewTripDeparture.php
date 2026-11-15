<?php

namespace App\Filament\Resources\TripDepartures\Pages;

use App\Filament\Resources\TripDepartures\TripDepartureResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

/** Read-only page for staff who may view but not edit (the form, disabled). */
class ViewTripDeparture extends ViewRecord
{
    protected static string $resource = TripDepartureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
