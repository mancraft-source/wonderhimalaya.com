<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
use App\Support\AccessControl;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Password;

class UserForm
{
    /**
     * Roles offered to the current user: theirs to grant (see AccessControl), plus any the record
     * already holds so saving never silently strips a role the editor could not have assigned.
     */
    public static function assignableRoles(Builder $query, ?User $record): Builder
    {
        $actor = auth()->user();

        if (! $actor instanceof User) {
            return $query->whereRaw('1 = 0');
        }

        if ($actor->isAdmin()) {
            return $query;
        }

        $held = $record?->roles()->pluck('id')->all() ?? [];

        return $query->where(fn (Builder $roles): Builder => AccessControl::assignableRolesQuery($actor, $roles)
            ->orWhereIn($roles->qualifyColumn('id'), $held));
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make('Account')->columns(2)->schema([
                    TextInput::make('name')->required()->maxLength(255),
                    TextInput::make('email')->email()->required()->maxLength(255)->unique(ignoreRecord: true),
                    TextInput::make('phone')->tel()->maxLength(50),
                    Toggle::make('is_active')->label('Active')->default(true)->inline(false)
                        ->helperText('Inactive users cannot sign in to the admin.'),
                    Select::make('roles')
                        ->relationship('roles', 'name', fn (Builder $query, ?User $record): Builder => self::assignableRoles($query, $record))
                        ->multiple()
                        ->preload()
                        ->live()
                        ->columnSpanFull()
                        ->helperText('A person can hold several roles; their access is everything those roles allow. Roles are set up in Settings → Roles & permissions.'),
                    TextEntry::make('effective_access')
                        ->label('Effective access')
                        ->columnSpanFull()
                        ->state(fn (Get $get): string => AccessControl::summaryForRoles(array_filter((array) $get('roles'))))
                        ->color('gray'),
                ]),
                Section::make('Password')
                    ->description(fn (string $operation): ?string => $operation === 'edit' ? 'Leave blank to keep the current password.' : null)
                    ->columns(2)
                    ->schema([
                        TextInput::make('password')
                            ->password()
                            ->revealable()
                            ->rule(Password::default())
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->confirmed()
                            ->autocomplete('new-password'),
                        TextInput::make('password_confirmation')
                            ->password()
                            ->revealable()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(false)
                            ->autocomplete('new-password'),
                    ]),
            ]);
    }
}
