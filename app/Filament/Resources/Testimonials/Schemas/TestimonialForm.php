<?php

namespace App\Filament\Resources\Testimonials\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TestimonialForm
{
    public const SOURCES = [
        'google' => 'Google',
        'tripadvisor' => 'Tripadvisor',
        'manual' => 'Manual',
    ];

    /** @return array<int, string> */
    public static function ratingOptions(): array
    {
        return collect(range(5, 1))
            ->mapWithKeys(fn (int $n): array => [$n => str_repeat('★', $n).str_repeat('☆', 5 - $n)])
            ->all();
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Review')
                    ->columns(2)
                    ->columnSpan(['lg' => 2])
                    ->schema([
                        TextInput::make('author_name')
                            ->label('Author')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('author_country')
                            ->label('Country')
                            ->maxLength(255),
                        TextInput::make('title')
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Textarea::make('body')
                            ->required()
                            ->rows(6)
                            ->columnSpanFull(),
                        Select::make('trip_id')
                            ->label('Trip')
                            ->relationship('trip', 'title')
                            ->searchable()
                            ->preload()
                            ->columnSpanFull(),
                    ]),
                Group::make([
                    Section::make('Details')
                        ->schema([
                            Select::make('rating')
                                ->options(self::ratingOptions())
                                ->default(5)
                                ->selectablePlaceholder(false)
                                ->required(),
                            Select::make('source')
                                ->options(self::SOURCES)
                                ->default('manual')
                                ->selectablePlaceholder(false)
                                ->required(),
                            TextInput::make('source_url')
                                ->label('Source URL')
                                ->url()
                                ->maxLength(500),
                            DatePicker::make('reviewed_at')
                                ->label('Review date')
                                ->maxDate(now()),
                        ]),
                    Section::make('Visibility')
                        ->schema([
                            Toggle::make('is_published')
                                ->label('Published')
                                ->default(true),
                            Toggle::make('is_featured')
                                ->label('Featured'),
                        ]),
                ])->columnSpan(['lg' => 1]),
            ]);
    }
}
