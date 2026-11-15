<?php

namespace App\Filament\Resources\Roles\Schemas;

use App\Models\User;
use App\Support\AccessControl;
use App\Support\Permissions;
use Closure;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Spatie\Permission\Models\Role;

class RoleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                TextEntry::make('locked_notice')
                    ->hiddenLabel()
                    ->icon(Heroicon::OutlinedLockClosed)
                    ->iconColor('warning')
                    ->state('The admin role is locked: administrators always have every permission, and the role cannot be edited or deleted.')
                    ->visible(fn (?Role $record): bool => $record && AccessControl::isLockedRole($record)),
                Section::make('Role')
                    ->columns(2)
                    ->schema([
                        self::nameInput(),
                        Textarea::make('description')
                            ->maxLength(255)
                            ->rows(2)
                            ->placeholder('e.g. Sales team: works leads and bookings, no catalogue access'),
                    ]),
                Section::make('Permissions by section')
                    ->description('What this role can do in each part of the admin. Create, edit or delete also grants view. Click a column or group heading to select it all.')
                    ->schema([
                        ViewField::make('section_permissions')
                            ->hiddenLabel()
                            ->view('filament.roles.permission-matrix')
                            ->viewData([
                                'groups' => Permissions::groupedSections(),
                                'abilities' => Permissions::ABILITIES,
                                'sectionAbilities' => collect(Permissions::SECTIONS)->keys()->mapWithKeys(fn (string $section): array => [$section => Permissions::abilitiesFor($section)])->all(),
                                'grantable' => self::grantable(),
                            ])
                            ->default([])
                            ->rule('array'),
                    ]),
                Section::make('Special permissions')
                    ->description('Rights that are not tied to one section.')
                    ->schema([
                        CheckboxList::make('special_permissions')
                            ->hiddenLabel()
                            ->options(collect(Permissions::SPECIAL)->map(fn (array $special): string => $special['label'])->all())
                            ->descriptions(collect(Permissions::SPECIAL)->map(fn (array $special): string => $special['description'])->all())
                            ->disableOptionWhen(fn (string $value): bool => ! in_array($value, self::grantable(), true))
                            ->columns(2)
                            ->bulkToggleable()
                            ->default([]),
                    ]),
            ]);
    }

    public static function nameInput(bool $ignoreRecord = true): TextInput
    {
        return TextInput::make('name')
            ->required()
            ->maxLength(125)
            ->unique(table: config('permission.table_names.roles'), column: 'name', ignoreRecord: $ignoreRecord)
            ->rule(fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                if (strtolower(trim((string) $value)) === Permissions::ADMIN_ROLE) {
                    $fail('"admin" is reserved for the built-in administrator role.');
                }
            })
            ->helperText('Shown on staff accounts, e.g. "sales" or "content editor".');
    }

    /** @return array<int, string> */
    public static function grantable(): array
    {
        $actor = auth()->user();

        return $actor instanceof User ? AccessControl::grantablePermissions($actor) : [];
    }
}
