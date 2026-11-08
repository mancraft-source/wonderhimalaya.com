<?php

namespace App\Filament\Resources\Destinations\Schemas;

use App\Filament\Support\FormHelpers;
use App\Filament\Support\SeoFields;
use App\Models\Destination;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class DestinationForm
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
                        Select::make('parent_id')
                            ->label('Parent region')
                            ->relationship('parent', 'name', fn (Builder $query, ?Model $record) => $query->when($record, fn (Builder $q) => $q->whereKeyNot($record->getKey())))
                            ->searchable()
                            ->preload(),
                        TextInput::make('tagline')->maxLength(255),
                        Textarea::make('intro')->rows(3)->columnSpanFull(),
                        RichEditor::make('description')->columnSpanFull(),
                        Repeater::make('highlights')
                            ->simple(TextInput::make('text')->required()->maxLength(500))
                            ->defaultItems(0)
                            ->columnSpanFull(),
                    ]),
                    SeoFields::section(Destination::class),
                ]),
                Group::make()->schema([
                    Section::make()->schema([
                        SpatieMediaLibraryFileUpload::make('hero')->label('Hero image')->collection('hero')->image()->imageEditor()->maxSize(8192),
                        Toggle::make('is_featured')->label('Featured'),
                        TextInput::make('sort')->numeric()->default(0),
                    ]),
                ]),
            ]);
    }
}
