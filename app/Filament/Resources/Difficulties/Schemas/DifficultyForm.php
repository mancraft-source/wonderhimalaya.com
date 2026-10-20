<?php

namespace App\Filament\Resources\Difficulties\Schemas;

use App\Filament\Support\FormHelpers;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class DifficultyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                FormHelpers::titleWithSlug('name'),
                FormHelpers::slug(),
                Select::make('level')
                    ->options([1 => '1 — Easy', 2 => '2 — Moderate', 3 => '3 — Challenging', 4 => '4 — Strenuous', 5 => '5 — Extreme'])
                    ->default(1)
                    ->required(),
                Textarea::make('description')->rows(3)->columnSpanFull(),
            ]);
    }
}
