<?php

namespace App\Filament\Support;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Str;

/** Reusable form building blocks shared by admin resources. */
final class FormHelpers
{
    /** Title/name input that fills the slug while creating (or while the slug is empty). */
    public static function titleWithSlug(string $field = 'title', string $slugField = 'slug'): TextInput
    {
        return TextInput::make($field)
            ->required()
            ->maxLength(255)
            ->live(onBlur: true)
            ->afterStateUpdated(function (Set $set, Get $get, ?string $state, string $operation) use ($slugField): void {
                if ($operation === 'create' || blank($get($slugField))) {
                    $set($slugField, Str::slug((string) $state));
                }
            });
    }

    public static function slug(string $field = 'slug'): TextInput
    {
        return TextInput::make($field)
            ->required()
            ->maxLength(255)
            ->alphaDash()
            ->unique(ignoreRecord: true)
            ->helperText('Changing the slug of a published item creates a 301 redirect automatically.');
    }

    public static function seoSection(): Section
    {
        return Section::make('SEO')
            ->description('Overrides for search engines and social sharing. Leave blank to use defaults.')
            ->schema([
                TextInput::make('seo.title')
                    ->label('Meta title')
                    ->maxLength(70)
                    ->live(debounce: 500)
                    ->hint(fn (?string $state): string => mb_strlen((string) $state).' / 60'),
                Textarea::make('seo.description')
                    ->label('Meta description')
                    ->rows(3)
                    ->maxLength(320)
                    ->live(debounce: 500)
                    ->hint(fn (?string $state): string => mb_strlen((string) $state).' / 160'),
                TextInput::make('seo.canonical')
                    ->label('Canonical URL')
                    ->url()
                    ->maxLength(500),
                Toggle::make('seo.noindex')
                    ->label('Hide from search engines (noindex)'),
            ])
            ->columns(1);
    }
}
