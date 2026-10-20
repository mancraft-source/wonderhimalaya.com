<?php

namespace App\Filament\Resources\Destinations\Tables;

use App\Models\Destination;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
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
            ])
            ->filters([
                SelectFilter::make('parent_id')->label('Parent')->relationship('parent', 'name'),
                TernaryFilter::make('is_featured')->label('Featured'),
                TernaryFilter::make('top_level')
                    ->label('Top-level only')
                    ->queries(
                        true: fn (Builder $q) => $q->whereNull('parent_id'),
                        false: fn (Builder $q) => $q->whereNotNull('parent_id'),
                    ),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
