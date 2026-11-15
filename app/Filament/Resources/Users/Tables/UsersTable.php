<?php

namespace App\Filament\Resources\Users\Tables;

use App\Filament\Resources\Users\UserActions;
use App\Filament\Support\TableEmptyState;
use App\Models\User;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->persistSearchInSession()
            ->persistColumnSearchesInSession()
            ->emptyStateIcon(TableEmptyState::icon($table, Heroicon::OutlinedUserCircle))
            ->emptyStateHeading(TableEmptyState::heading($table, 'No staff accounts yet'))
            ->emptyStateDescription(TableEmptyState::description($table, 'Staff accounts sign in to this admin, and each person\'s role decides what they can see and do.'))
            ->emptyStateActions([CreateAction::make()])
            ->modifyQueryUsing(fn (Builder $query) => $query->with('roles')->withCount('tokens'))
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')->searchable()->sortable()->weight('medium'),
                TextColumn::make('email')->searchable()->copyable(),
                TextColumn::make('roles.name')->label('Roles')->badge(),
                IconColumn::make('is_active')->label('Active')->boolean(),
                IconColumn::make('mfa')
                    ->label('2FA')
                    ->state(fn (User $record): bool => filled($record->getRawOriginal('app_authentication_secret')))
                    ->boolean(),
                TextColumn::make('tokens_count')->label('API tokens')->toggleable(),
                TextColumn::make('last_login_at')->since()->placeholder('Never')->sortable(),
            ])
            ->filters([
                SelectFilter::make('roles')->relationship('roles', 'name')->multiple()->preload(),
                TernaryFilter::make('is_active')->label('Active'),
            ])
            ->recordActions([
                EditAction::make(),
                ActionGroup::make([
                    UserActions::revokeTokens(),
                    DeleteAction::make()->hidden(fn (User $record): bool => $record->is(auth()->user())),
                ]),
            ]);
    }
}
