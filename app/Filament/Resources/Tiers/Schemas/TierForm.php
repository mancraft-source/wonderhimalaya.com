<?php

namespace App\Filament\Resources\Tiers\Schemas;

use App\Filament\Support\FormHelpers;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TierForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                FormHelpers::titleWithSlug('name'),
                FormHelpers::slug(),
                Textarea::make('description')->rows(3)->columnSpanFull(),
                TextInput::make('sort')->numeric()->default(0),
            ]);
    }
}
