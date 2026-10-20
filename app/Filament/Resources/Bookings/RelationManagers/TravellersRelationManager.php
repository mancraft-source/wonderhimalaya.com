<?php

namespace App\Filament\Resources\Bookings\RelationManagers;

use App\Support\Permissions;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Travellers on a booking. Passport, insurance and medical fields are encrypted
 * at rest and only visible to staff who can manage payments (or admins).
 */
class TravellersRelationManager extends RelationManager
{
    protected static string $relationship = 'travellers';

    protected static string|\BackedEnum|null $icon = Heroicon::OutlinedIdentification;

    public function isReadOnly(): bool
    {
        return false;
    }

    public static function canSeeSensitive(): bool
    {
        return (bool) auth()->user()?->can(Permissions::PAYMENTS);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('first_name')->required()->maxLength(255),
                TextInput::make('last_name')->maxLength(255),
                TextInput::make('email')->email()->maxLength(255),
                TextInput::make('phone')->tel()->maxLength(50),
                Select::make('gender')->options(['female' => 'Female', 'male' => 'Male', 'other' => 'Other / prefer not to say']),
                TextInput::make('nationality')->maxLength(100),
                TextInput::make('dietary')->maxLength(255),
                Toggle::make('is_lead')->label('Lead traveller')->inline(false),
                Section::make('Sensitive (encrypted)')
                    ->description('Passport, insurance and medical details.')
                    ->columnSpanFull()
                    ->columns(2)
                    ->visible(fn (): bool => self::canSeeSensitive())
                    ->schema([
                        DatePicker::make('date_of_birth')->native(false),
                        TextInput::make('passport_number')->password()->revealable()->maxLength(50),
                        DatePicker::make('passport_expiry')->native(false),
                        TextInput::make('insurance_provider')->maxLength(255),
                        TextInput::make('insurance_policy')->password()->revealable()->maxLength(255),
                        Textarea::make('medical_notes')->rows(3)->columnSpanFull(),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('first_name')
            ->columns([
                TextColumn::make('first_name')->label('Name')->formatStateUsing(fn ($record): string => trim($record->first_name.' '.$record->last_name)),
                IconColumn::make('is_lead')->label('Lead')->boolean(),
                TextColumn::make('email')->placeholder('—'),
                TextColumn::make('nationality')->placeholder('—'),
                TextColumn::make('passport_number')
                    ->label('Passport')
                    ->state(fn ($record): ?string => $record->passport_number ? '••••'.substr((string) $record->passport_number, -3) : null)
                    ->placeholder('—')
                    ->visible(fn (): bool => self::canSeeSensitive()),
                TextColumn::make('dietary')->placeholder('—')->toggleable(),
            ])
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }
}
