<?php

namespace App\Filament\Resources\Tags\Tables;

use App\Filament\Support\TableEmptyState;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TagsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->persistSearchInSession()
            ->persistColumnSearchesInSession()
            ->emptyStateIcon(TableEmptyState::icon($table, Heroicon::OutlinedTag))
            ->emptyStateHeading(TableEmptyState::heading($table, 'No tags yet'))
            ->emptyStateDescription(TableEmptyState::description($table, 'Tags are short labels for grouping trips and blog posts.'))
            ->emptyStateActions([CreateAction::make()])
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount(['trips', 'posts']))
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('slug')->searchable(),
                TextColumn::make('trips_count')->label('Trips')->sortable(),
                TextColumn::make('posts_count')->label('Posts')->sortable(),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
