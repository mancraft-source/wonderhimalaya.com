<?php

namespace App\Filament\Support;

use Filament\Forms\Components\TextInput;
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
}
