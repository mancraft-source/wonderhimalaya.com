<?php

namespace App\Filament\Support;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Str;

/**
 * Reusable form fields shared by the Content resources.
 */
final class ContentFields
{
    /** Title input that fills the slug while creating a record (not when editing, to keep URLs stable). */
    public static function title(string $name = 'title', string $slugField = 'slug'): TextInput
    {
        return TextInput::make($name)
            ->required()
            ->maxLength(255)
            ->live(onBlur: true)
            ->afterStateUpdated(function (Set $set, ?string $state, string $operation) use ($slugField): void {
                if ($operation !== 'edit') {
                    $set($slugField, Str::slug((string) $state));
                }
            });
    }

    public static function slug(bool $unique = true, string $name = 'slug'): TextInput
    {
        $field = TextInput::make($name)
            ->required()
            ->maxLength(255)
            ->alphaDash()
            ->helperText('Used in the URL. Changing it creates a 301 from the old address.');

        return $unique ? $field->unique(ignoreRecord: true) : $field;
    }

    /** SEO overrides stored in the `seo` JSON column. */
    public static function seoSection(): Section
    {
        return Section::make('SEO')
            ->description('Leave blank to use the title and excerpt.')
            ->collapsible()
            ->collapsed()
            ->columns(2)
            ->schema([
                TextInput::make('seo.title')
                    ->label('Meta title')
                    ->maxLength(70)
                    ->live(debounce: 500)
                    ->hint(fn (?string $state): string => strlen((string) $state).'/60')
                    ->columnSpanFull(),
                Textarea::make('seo.description')
                    ->label('Meta description')
                    ->rows(3)
                    ->maxLength(300)
                    ->live(debounce: 500)
                    ->hint(fn (?string $state): string => strlen((string) $state).'/160')
                    ->columnSpanFull(),
                TextInput::make('seo.canonical')
                    ->label('Canonical URL')
                    ->url()
                    ->maxLength(500),
                Toggle::make('seo.noindex')
                    ->label('Hide from search engines (noindex)')
                    ->inline(false),
            ]);
    }
}
