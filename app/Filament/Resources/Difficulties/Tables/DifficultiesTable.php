<?php

namespace App\Filament\Resources\Difficulties\Tables;

use App\Filament\Support\TableEmptyState;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DifficultiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->persistSearchInSession()
            ->persistColumnSearchesInSession()
            ->emptyStateIcon(TableEmptyState::icon($table, Heroicon::OutlinedChartBar))
            ->emptyStateHeading(TableEmptyState::heading($table, 'No difficulty grades yet'))
            ->emptyStateDescription(TableEmptyState::description($table, 'Difficulty grades tell travellers how demanding a trip is.'))
            ->emptyStateActions([CreateAction::make()])
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount('trips'))
            ->defaultSort('level')
            ->columns([
                TextColumn::make('level')->badge()->sortable(),
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('slug')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('trips_count')->label('Trips')->sortable(),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
