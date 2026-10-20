<?php

namespace App\Filament\Resources\Companies\Schemas;

use App\Enums\CompanyType;
use App\Filament\Support\CrmFields;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CompanyForm
{
    public const STATUSES = ['prospect' => 'Prospect', 'active' => 'Active partner', 'inactive' => 'Inactive'];

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Company')->columnSpan(2)->columns(2)->schema([
                    TextInput::make('name')->required()->maxLength(255)->columnSpanFull(),
                    Select::make('type')->options(CompanyType::class)->default(CompanyType::Agency)->required(),
                    TextInput::make('country')->maxLength(100),
                    TextInput::make('website')->url()->maxLength(255),
                    TextInput::make('email')->email()->maxLength(255),
                    TextInput::make('phone')->tel()->maxLength(50),
                    Textarea::make('notes')->rows(4)->columnSpanFull(),
                ]),
                Section::make()->schema([
                    Select::make('status')->options(self::STATUSES)->default('prospect')->required(),
                    CrmFields::ownerSelect(),
                ]),
            ]);
    }
}
