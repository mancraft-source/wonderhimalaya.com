<?php

namespace App\Filament\Resources\Menus\Tables;

use App\Filament\Resources\Menus\Schemas\MenuForm;
use App\Filament\Support\TableEmptyState;
use App\Models\Menu;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MenusTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->persistSearchInSession()
            ->persistColumnSearchesInSession()
            ->emptyStateIcon(TableEmptyState::icon($table, Heroicon::OutlinedBars3))
            ->emptyStateHeading(TableEmptyState::heading($table, 'No menus yet'))
            ->emptyStateDescription(TableEmptyState::description($table, 'Menus hold the navigation links shown on the website.'))
            ->emptyStateActions([CreateAction::make()])
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('location')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => MenuForm::LOCATIONS[$state] ?? (string) $state)
                    ->sortable(),
                TextColumn::make('items_count')
                    ->label('Items')
                    ->state(fn (Menu $record): int => collect($record->items ?? [])
                        ->sum(fn (array $item): int => 1 + count($item['children'] ?? []))),
                TextColumn::make('updated_at')
                    ->since()
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
