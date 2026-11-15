<?php

namespace App\Filament\Resources\Roles\Tables;

use App\Filament\Resources\Roles\RolePermissions;
use App\Filament\Resources\Roles\RoleResource;
use App\Filament\Resources\Roles\Schemas\RoleForm;
use App\Filament\Support\TableEmptyState;
use App\Support\AccessControl;
use App\Support\Permissions;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Role;

class RolesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->persistSortInSession()
            ->persistSearchInSession()
            ->emptyStateIcon(TableEmptyState::icon($table, Heroicon::OutlinedShieldCheck))
            ->emptyStateHeading(TableEmptyState::heading($table, 'No roles yet'))
            ->emptyStateDescription(TableEmptyState::description($table, 'Roles bundle permissions so you can give staff the access their job needs.'))
            ->emptyStateActions([CreateAction::make()])
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount(['users', 'permissions']))
            ->defaultSort('name')
            ->recordUrl(fn (Role $record): string => RoleResource::getUrl(RoleResource::canEdit($record) ? 'edit' : 'view', ['record' => $record]))
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->icon(fn (Role $record): ?Heroicon => AccessControl::isLockedRole($record) ? Heroicon::OutlinedLockClosed : null)
                    ->description(fn (Role $record): ?string => $record->description),
                TextColumn::make('users_count')
                    ->label('People')
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'primary' : 'gray')
                    ->sortable(),
                TextColumn::make('access')
                    ->label('Access')
                    ->state(fn (Role $record): string => AccessControl::isLockedRole($record)
                        ? 'Everything (locked)'
                        : Permissions::summarize($record->permissions->pluck('name')))
                    ->wrap()
                    ->lineClamp(2)
                    ->color('gray'),
                TextColumn::make('updated_at')->since()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                ViewAction::make()->visible(fn (Role $record): bool => ! RoleResource::canEdit($record)),
                EditAction::make(),
                ActionGroup::make([
                    self::duplicateAction(),
                    self::deleteAction(),
                ]),
            ]);
    }

    /** Copy a role's permissions into a new role. */
    public static function duplicateAction(): Action
    {
        return Action::make('duplicate')
            ->label('Duplicate')
            ->icon(Heroicon::OutlinedDocumentDuplicate)
            ->color('gray')
            ->authorize(fn (): bool => RoleResource::canCreate())
            ->modalHeading(fn (Role $record): string => 'Duplicate '.$record->name)
            ->modalDescription('Creates a new role with the same description and permissions, ready to adjust.')
            ->modalSubmitActionLabel('Duplicate')
            ->fillForm(fn (Role $record): array => ['name' => $record->name.' copy'])
            ->schema([RoleForm::nameInput(ignoreRecord: false)->helperText(null)])
            ->action(function (Role $record, array $data, Action $action): void {
                $permissions = RolePermissions::fill($record);
                $permissions = [...$permissions['section_permissions'], ...$permissions['special_permissions']];

                RolePermissions::guardEscalation($permissions);

                $copy = Role::create([
                    'name' => trim($data['name']),
                    'description' => $record->description,
                    'guard_name' => 'web',
                ]);

                AccessControl::syncRole($copy, $permissions, 'created', auth()->user());

                Notification::make()->success()->title('Role duplicated')->body($copy->name)->send();

                $action->redirect(RoleResource::getUrl('edit', ['record' => $copy]));
            });
    }

    public static function deleteAction(): DeleteAction
    {
        return DeleteAction::make()
            ->authorizationTooltip()
            ->before(fn (Role $record) => AccessControl::logRoleDeleted($record, auth()->user()));
    }
}
