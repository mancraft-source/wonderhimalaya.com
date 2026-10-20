<?php

namespace App\Filament\Resources\Redirects\Schemas;

use App\Models\Redirect;
use Closure;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RedirectForm
{
    public const STATUS_CODES = [
        301 => '301 — Moved permanently',
        302 => '302 — Found (temporary)',
        410 => '410 — Gone',
    ];

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('from_path')
                            ->label('From')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('/old-page')
                            ->helperText('Path only; lower-cased and trimmed of slashes on save.')
                            ->rule(fn (?Redirect $record): Closure => function (string $attribute, mixed $value, Closure $fail) use ($record): void {
                                $exists = Redirect::query()
                                    ->where('from_path', Redirect::normalize((string) $value))
                                    ->when($record, fn ($q) => $q->whereKeyNot($record->getKey()))
                                    ->exists();

                                if ($exists) {
                                    $fail('A redirect from this path already exists.');
                                }
                            })
                            ->dehydrateStateUsing(fn (?string $state): string => Redirect::normalize((string) $state)),
                        TextInput::make('to_path')
                            ->label('To')
                            ->required()
                            ->maxLength(500)
                            ->placeholder('/new-page or https://…')
                            ->dehydrateStateUsing(fn (?string $state): string => str_starts_with((string) $state, 'http')
                                ? (string) $state
                                : Redirect::normalize((string) $state)),
                        Select::make('status_code')
                            ->label('Type')
                            ->options(self::STATUS_CODES)
                            ->default(301)
                            ->selectablePlaceholder(false)
                            ->required(),
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->inline(false),
                        TextInput::make('hits')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false)
                            ->hiddenOn('create'),
                        DateTimePicker::make('last_hit_at')
                            ->label('Last hit')
                            ->disabled()
                            ->dehydrated(false)
                            ->hiddenOn('create'),
                    ]),
            ]);
    }
}
