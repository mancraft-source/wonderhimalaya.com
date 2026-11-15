<?php

namespace App\Filament\Resources\Destinations;

use App\Filament\Resources\Destinations\Pages\CreateDestination;
use App\Filament\Resources\Destinations\Pages\EditDestination;
use App\Filament\Resources\Destinations\Pages\ListDestinations;
use App\Filament\Resources\Destinations\Schemas\DestinationForm;
use App\Filament\Resources\Destinations\Tables\DestinationsTable;
use App\Filament\Support\GlobalSearch;
use App\Models\Destination;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class DestinationResource extends Resource
{
    protected static ?string $model = Destination::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAsiaAustralia;

    protected static string|UnitEnum|null $navigationGroup = 'Catalogue';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'name';

    protected static int $globalSearchResultsLimit = GlobalSearch::RESULTS_LIMIT;

    public static function form(Schema $schema): Schema
    {
        return DestinationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DestinationsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    /** @return array<int, string> */
    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'slug'];
    }

    /** e.g. "Everest · 12 trips" */
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        /** @var Destination $record */
        return GlobalSearch::line([$record->parent?->name, $record->trips_count.' '.str('trip')->plural((int) $record->trips_count)]);
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with('parent')->withCount('trips')->orderBy('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDestinations::route('/'),
            'create' => CreateDestination::route('/create'),
            'edit' => EditDestination::route('/{record}/edit'),
        ];
    }
}
