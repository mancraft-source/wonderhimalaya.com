<?php

namespace App\Filament\Resources\Tiers;

use App\Filament\Resources\Tiers\Pages\CreateTier;
use App\Filament\Resources\Tiers\Pages\EditTier;
use App\Filament\Resources\Tiers\Pages\ListTiers;
use App\Filament\Resources\Tiers\Schemas\TierForm;
use App\Filament\Resources\Tiers\Tables\TiersTable;
use App\Models\Tier;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class TierResource extends Resource
{
    protected static ?string $model = Tier::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static string|UnitEnum|null $navigationGroup = 'Catalogue';

    protected static ?int $navigationSort = 6;

    public static function form(Schema $schema): Schema
    {
        return TierForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TiersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTiers::route('/'),
            'create' => CreateTier::route('/create'),
            'edit' => EditTier::route('/{record}/edit'),
        ];
    }
}
