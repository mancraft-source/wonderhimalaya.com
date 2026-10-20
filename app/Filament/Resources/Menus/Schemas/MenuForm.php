<?php

namespace App\Filament\Resources\Menus\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MenuForm
{
    public const LOCATIONS = [
        'header' => 'Header',
        'footer_explore' => 'Footer — Explore',
        'footer_company' => 'Footer — Company',
        'footer_legal' => 'Footer — Legal',
    ];

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Select::make('location')
                            ->options(self::LOCATIONS)
                            ->required()
                            ->unique(ignoreRecord: true),
                    ]),
                Section::make('Items')
                    ->columnSpanFull()
                    ->schema([
                        Repeater::make('items')
                            ->hiddenLabel()
                            ->schema([
                                ...self::linkFields(),
                                Repeater::make('children')
                                    ->label('Sub-items')
                                    ->schema(self::linkFields())
                                    ->columns(3)
                                    ->defaultItems(0)
                                    ->collapsible()
                                    ->collapsed()
                                    ->reorderable()
                                    ->itemLabel(fn (array $state): ?string => $state['label'] ?? null)
                                    ->addActionLabel('Add sub-item')
                                    ->columnSpanFull(),
                            ])
                            ->columns(3)
                            ->defaultItems(0)
                            ->collapsible()
                            ->reorderable()
                            ->cloneable()
                            ->itemLabel(fn (array $state): ?string => $state['label'] ?? null)
                            ->addActionLabel('Add menu item'),
                    ]),
            ]);
    }

    /** @return array<int, TextInput|Toggle> */
    protected static function linkFields(): array
    {
        return [
            TextInput::make('label')
                ->required()
                ->live(onBlur: true),
            TextInput::make('url')
                ->label('URL')
                ->required()
                ->placeholder('/trekking or https://…'),
            Toggle::make('open_new_tab')
                ->label('Open in new tab')
                ->inline(false),
        ];
    }
}
