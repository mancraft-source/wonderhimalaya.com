<?php

namespace App\Filament\Resources\Destinations\Tables;

use App\Filament\Support\SeoFields;
use App\Filament\Support\SiteLinkActions;
use App\Filament\Support\TableEmptyState;
use App\Models\Destination;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DestinationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->persistSearchInSession()
            ->persistColumnSearchesInSession()
            ->emptyStateIcon(TableEmptyState::icon($table, Heroicon::OutlinedGlobeAsiaAustralia))
            ->emptyStateHeading(TableEmptyState::heading($table, 'No destinations yet'))
            ->emptyStateDescription(TableEmptyState::description($table, 'Destinations are the regions trips are grouped under, and each one gets its own page on the website.'))
            ->emptyStateActions([CreateAction::make()])
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['parent', 'media'])->withCount('trips'))
            ->defaultSort('sort')
            ->reorderable('sort')
            ->columns([
                SpatieMediaLibraryImageColumn::make('hero')->label('')->collection('hero')->conversion('thumb')->imageSize(40)->square(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Destination $record): ?string => $record->parent ? '↳ '.$record->parent->name : null),
                TextColumn::make('parent.name')->label('Parent')->sortable()->toggleable(),
                TextColumn::make('slug')->searchable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('trips_count')->label('Trips')->sortable(),
                IconColumn::make('is_featured')->label('Featured')->boolean(),
                SeoFields::scoreColumn(),
            ])
            ->filters([
                SeoFields::scoreFilter(),
                SelectFilter::make('parent_id')->label('Parent')->relationship('parent', 'name'),
                TernaryFilter::make('is_featured')->label('Featured'),
                TernaryFilter::make('top_level')
                    ->label('Top-level only')
                    ->queries(
                        true: fn (Builder $q) => $q->whereNull('parent_id'),
                        false: fn (Builder $q) => $q->whereNotNull('parent_id'),
                    ),
            ])
            ->recordActions([SiteLinkActions::openOnSite(), EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
