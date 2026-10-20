<?php

namespace App\Filament\Resources\Contacts\Schemas;

use App\Filament\Support\CrmFields;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ContactForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Contact')->columnSpan(2)->columns(2)->schema([
                    TextInput::make('first_name')->required()->maxLength(255),
                    TextInput::make('last_name')->maxLength(255),
                    TextInput::make('email')->email()->required()->maxLength(255)->unique(ignoreRecord: true),
                    TextInput::make('phone')->tel()->maxLength(50),
                    TextInput::make('whatsapp')->tel()->maxLength(50),
                    TextInput::make('country')->maxLength(100),
                    Textarea::make('notes')->rows(4)->columnSpanFull(),
                ]),
                Group::make()->schema([
                    Section::make()->schema([
                        Select::make('company_id')->label('Company')->relationship('company', 'name')->searchable()->preload(),
                        CrmFields::ownerSelect(),
                        TextInput::make('source')->maxLength(255),
                    ]),
                    Section::make('Consent')->schema([
                        Toggle::make('marketing_consent')->label('Marketing consent'),
                        DateTimePicker::make('consent_at')->label('Consent given at')->native(false),
                    ]),
                ]),
            ]);
    }
}
