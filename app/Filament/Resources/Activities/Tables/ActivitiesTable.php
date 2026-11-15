<?php

namespace App\Filament\Resources\Activities\Tables;

use App\Filament\Support\SeoFields;
use App\Filament\Support\SiteLinkActions;
use App\Filament\Support\TableEmptyState;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ActivitiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->persistSearchInSession()
            ->persistColumnSearchesInSession()
            ->emptyStateIcon(TableEmptyState::icon($table, Heroicon::OutlinedBolt))
            ->emptyStateHeading(TableEmptyState::heading($table, 'No activities yet'))
            ->emptyStateDescription(TableEmptyState::description($table, 'Activities group trips by style, and each one gets its own page on the website.'))
            ->emptyStateActions([CreateAction::make()])
            ->modifyQueryUsing(fn (Builder $query) => $query->with('media')->withCount('trips'))
            ->defaultSort('sort')
            ->reorderable('sort')
            ->columns([
                SpatieMediaLibraryImageColumn::make('hero')->label('')->collection('hero')->conversion('thumb')->imageSize(40)->square(),
                TextColumn::make('name')->searchable()->sortable()->description(fn ($record) => $record->tagline),
                TextColumn::make('slug')->searchable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('trips_count')->label('Trips')->sortable(),
                IconColumn::make('is_featured')->label('Featured')->boolean(),
                SeoFields::scoreColumn(),
            ])
            ->filters([SeoFields::scoreFilter()])
            ->recordActions([SiteLinkActions::openOnSite(), EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
