<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Password;

class UserForm
{
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
                        ->relationship('roles', 'name', fn (Builder $query) => auth()->user()?->isAdmin() ? $query : $query->where('name', '!=', 'admin'))
                        ->multiple()
                        ->preload()
                        ->columnSpanFull()
                        ->helperText('admin: everything · manager: all except users · sales: CRM & payments · content: catalogue & content'),
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
