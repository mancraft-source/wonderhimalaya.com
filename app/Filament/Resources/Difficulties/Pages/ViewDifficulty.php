<?php

namespace App\Filament\Resources\Difficulties\Pages;

use App\Filament\Resources\Difficulties\DifficultyResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

/** Read-only page for staff who may view but not edit (the form, disabled). */
class ViewDifficulty extends ViewRecord
{
    protected static string $resource = DifficultyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
