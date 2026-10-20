<?php

namespace App\Filament\Resources\Tags\Schemas;

use App\Filament\Support\FormHelpers;
use Filament\Schemas\Schema;

class TagForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                FormHelpers::titleWithSlug('name'),
                FormHelpers::slug(),
            ]);
    }
}
