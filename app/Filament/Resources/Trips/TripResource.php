<?php

namespace App\Filament\Resources\Trips;

use App\Filament\Resources\Trips\Pages\CreateTrip;
use App\Filament\Resources\Trips\Pages\EditTrip;
use App\Filament\Resources\Trips\Pages\ListTrips;
use App\Filament\Resources\Trips\RelationManagers\DeparturesRelationManager;
use App\Filament\Resources\Trips\RelationManagers\TestimonialsRelationManager;
use App\Filament\Resources\Trips\Schemas\TripForm;
use App\Filament\Resources\Trips\Tables\TripsTable;
use App\Filament\Support\GlobalSearch;
use App\Models\Trip;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class TripResource extends Resource
{
    protected static ?string $model = Trip::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMap;

    protected static string|UnitEnum|null $navigationGroup = 'Catalogue';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'title';

    protected static int $globalSearchResultsLimit = GlobalSearch::RESULTS_LIMIT;

    public static function form(Schema $schema): Schema
    {
        return TripForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TripsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            DeparturesRelationManager::class,
            TestimonialsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTrips::route('/'),
            'create' => CreateTrip::route('/create'),
            'edit' => EditTrip::route('/{record}/edit'),
        ];
    }

    /** @return array<int, string> */
    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'trip_code', 'slug'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        /** @var Trip $record */
        return $record->trip_code ? $record->title.' ('.$record->trip_code.')' : $record->title;
    }

    /** e.g. "Everest · 14 days · from USD 1,290 · Published" */
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        /** @var Trip $record */
        $price = $record->displayPrice();

        return GlobalSearch::line([
            $record->destination?->name,
            $record->durationLabel(),
            $price !== null ? 'from '.GlobalSearch::money($price, $record->currency) : null,
            $record->status?->getLabel(),
        ]);
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with('destination')->orderBy('title');
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
