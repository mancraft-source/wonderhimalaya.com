<?php

namespace App\Filament\Resources\Trips\RelationManagers;

use App\Filament\Resources\TripDepartures\Schemas\TripDepartureForm;
use App\Filament\Resources\TripDepartures\Tables\TripDeparturesTable;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class DeparturesRelationManager extends RelationManager
{
    protected static string $relationship = 'departures';

    protected static string|\BackedEnum|null $icon = Heroicon::OutlinedCalendarDays;

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return TripDepartureForm::configure($schema, withTrip: false);
    }

    public function table(Table $table): Table
    {
        return TripDeparturesTable::configure($table, withTrip: false)
            ->recordTitleAttribute('start_date')
            ->headerActions([
                CreateAction::make(),
            ]);
    }
}
