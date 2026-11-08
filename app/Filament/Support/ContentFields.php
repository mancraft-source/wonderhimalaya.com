<?php

namespace App\Filament\Support;

use Filament\Forms\Components\TextInput;
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
}
