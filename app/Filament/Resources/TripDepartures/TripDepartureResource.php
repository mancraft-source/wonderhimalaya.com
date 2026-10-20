<?php

namespace App\Filament\Resources\TripDepartures;

use App\Filament\Resources\TripDepartures\Pages\CreateTripDeparture;
use App\Filament\Resources\TripDepartures\Pages\EditTripDeparture;
use App\Filament\Resources\TripDepartures\Pages\ListTripDepartures;
use App\Filament\Resources\TripDepartures\Schemas\TripDepartureForm;
use App\Filament\Resources\TripDepartures\Tables\TripDeparturesTable;
use App\Models\TripDeparture;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class TripDepartureResource extends Resource
{
    protected static ?string $model = TripDeparture::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|UnitEnum|null $navigationGroup = 'Catalogue';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Departures';

    protected static ?string $modelLabel = 'departure';

    public static function form(Schema $schema): Schema
    {
        return TripDepartureForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TripDeparturesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTripDepartures::route('/'),
            'create' => CreateTripDeparture::route('/create'),
            'edit' => EditTripDeparture::route('/{record}/edit'),
        ];
    }
}
