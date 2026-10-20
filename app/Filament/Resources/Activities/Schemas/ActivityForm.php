<?php

namespace App\Filament\Resources\Activities\Schemas;

use App\Filament\Support\FormHelpers;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ActivityForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Group::make()->columnSpan(2)->schema([
                    Section::make()->columns(2)->schema([
                        FormHelpers::titleWithSlug('name'),
                        FormHelpers::slug(),
                        TextInput::make('tagline')->maxLength(255),
                        TextInput::make('icon')->maxLength(255)->helperText('Icon name used on the public site (e.g. heroicon or SVG name).'),
                        RichEditor::make('description')->columnSpanFull(),
                    ]),
                    FormHelpers::seoSection(),
                ]),
                Section::make()->schema([
                    SpatieMediaLibraryFileUpload::make('hero')->label('Hero image')->collection('hero')->image()->imageEditor()->maxSize(8192),
                    Toggle::make('is_featured')->label('Featured'),
                    TextInput::make('sort')->numeric()->default(0),
                ]),
            ]);
    }
}
