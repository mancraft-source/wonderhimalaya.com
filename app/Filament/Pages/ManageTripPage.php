<?php

namespace App\Filament\Pages;

use App\Filament\Support\ContentEditorFields;
use App\Support\Content;
use BackedEnum;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * Copy shared by every trip page, stored in Setting "trip_page" (shape: Content::tripPageDefaults()).
 */
class ManageTripPage extends ContentSettingsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Trip page content';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Trip page content';

    protected static ?string $slug = 'content/trip-page';

    protected static function settingKey(): string
    {
        return 'trip_page';
    }

    protected static function contentDefaults(): array
    {
        return Content::tripPageDefaults();
    }

    protected static function currentContent(): array
    {
        return Content::tripPage();
    }

    protected function getHeaderActions(): array
    {
        return [$this->resetAction()];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Notes')
                    ->description('Short notes shown on every trip page. A trip can override the itinerary note on its own form.')
                    ->schema([
                        Textarea::make('itinerary_note')->label('Itinerary note')->rows(2),
                        Textarea::make('group_discount_note')->label('Group discount note')->rows(2),
                        Textarea::make('packing_note')->label('Packing note')->rows(2),
                    ]),
                Section::make('Default packing list')
                    ->description('Used on treks and climbs that have no packing list of their own.')
                    ->schema([
                        ContentEditorFields::packingGroups('packing_default')->hiddenLabel(),
                    ]),
                Section::make('Trust block')
                    ->description('Up to four reassurance tiles shown near the booking box.')
                    ->schema([
                        ContentEditorFields::iconTiles('trust', maxItems: 4)->hiddenLabel(),
                    ]),
            ]);
    }
}
