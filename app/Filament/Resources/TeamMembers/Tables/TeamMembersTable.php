<?php

namespace App\Filament\Resources\TeamMembers\Tables;

use App\Filament\Support\SeoFields;
use App\Filament\Support\SiteLinkActions;
use App\Filament\Support\TableEmptyState;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TeamMembersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->persistSearchInSession()
            ->persistColumnSearchesInSession()
            ->emptyStateIcon(TableEmptyState::icon($table, Heroicon::OutlinedUserGroup))
            ->emptyStateHeading(TableEmptyState::heading($table, 'No team members yet'))
            ->emptyStateDescription(TableEmptyState::description($table, 'Team members are shown on the website and can be credited as blog authors.'))
            ->emptyStateActions([CreateAction::make()])
            ->modifyQueryUsing(fn (Builder $query) => $query->with('media'))
            ->reorderable('sort')
            ->defaultSort('sort')
            ->columns([
                SpatieMediaLibraryImageColumn::make('photo')
                    ->label('')
                    ->collection('photo')
                    ->conversion('thumb')
                    ->circular()
                    ->imageHeight(40),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('role')
                    ->searchable()
                    ->color('gray'),
                IconColumn::make('is_guide')
                    ->label('Guide')
                    ->boolean(),
                ToggleColumn::make('is_published')
                    ->label('Published'),
                TextColumn::make('experience_years')
                    ->label('Experience')
                    ->suffix(' yrs')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                SeoFields::scoreColumn(),
            ])
            ->filters([
                SeoFields::scoreFilter(),
                TernaryFilter::make('is_guide')->label('Guide'),
                TernaryFilter::make('is_published')->label('Published'),
            ])
            ->recordActions([
                SiteLinkActions::openOnSite(),
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
