<?php

namespace App\Filament\Support;

use App\Models\Trip;
use App\Support\Content;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Illuminate\Support\Str;

/**
 * Field builders for the designed-template content editors (homepage, trip page, designed pages).
 * Field names and shapes follow the storage contract in App\Support\Content.
 */
final class ContentEditorFields
{
    /** Optional image stored on the public disk. */
    public static function image(string $name, string $directory = 'content'): FileUpload
    {
        return FileUpload::make($name)
            ->image()
            ->imageEditor()
            ->disk('public')
            ->directory($directory)
            ->visibility('public')
            ->maxSize(8192);
    }

    /** Repeater of {icon, title, text} tiles. */
    public static function iconTiles(string $name, ?int $maxItems = null): Repeater
    {
        return Repeater::make($name)
            ->schema([
                Select::make('icon')
                    ->options(Content::ICONS)
                    ->searchable()
                    ->required(),
                TextInput::make('title')->required()->maxLength(255)->live(onBlur: true),
                Textarea::make('text')->rows(2)->required()->columnSpanFull(),
            ])
            ->columns(2)
            ->collapsible()
            ->reorderable()
            ->defaultItems(0)
            ->maxItems($maxItems)
            ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
            ->addActionLabel('Add tile');
    }

    /** Repeater of {group, items[]} packing groups. */
    public static function packingGroups(string $name): Repeater
    {
        return Repeater::make($name)
            ->schema([
                TextInput::make('group')->required()->maxLength(255)->live(onBlur: true),
                Repeater::make('items')
                    ->simple(TextInput::make('item')->required()->maxLength(255))
                    ->defaultItems(0)
                    ->reorderable()
                    ->addActionLabel('Add item'),
            ])
            ->collapsible()
            ->collapsed()
            ->reorderable()
            ->defaultItems(0)
            ->itemLabel(fn (array $state): ?string => $state['group'] ?? null)
            ->addActionLabel('Add packing group');
    }

    /** @return array<int, string> */
    public static function publishedTripOptions(bool $withHeroImage = false): array
    {
        return Trip::query()
            ->published()
            ->when($withHeroImage, fn ($q) => $q->whereHas('media', fn ($m) => $m->where('collection_name', 'hero')))
            ->orderBy('title')
            ->pluck('title', 'id')
            ->all();
    }

    /** Human label for a content key, e.g. "hero_title_em" => "Hero title — italic accent". */
    public static function label(string $key): string
    {
        if (str_ends_with($key, '_title_em')) {
            return Str::ucfirst(str_replace('_', ' ', Str::beforeLast($key, '_em'))).' — italic accent words';
        }

        return Str::ucfirst(str_replace('_', ' ', $key));
    }

    /**
     * One form field per key of Content::pageDefaults($template), bound to "content.<key>".
     *
     * @return array<int, Component>
     */
    public static function pageTemplateFields(string $template): array
    {
        $fields = [];

        foreach (array_keys(Content::pageDefaults($template)) as $key) {
            $path = "content.{$key}";
            $label = self::label($key);

            $fields[] = match (true) {
                str_ends_with($key, '_image') => self::image($path, 'content/pages')->label($label),
                in_array($key, ['values', 'reasons', 'pillars'], true) => self::iconTiles($path)->label($label)->columnSpanFull(),
                $key === 'faqs' => Repeater::make($path)
                    ->label('FAQs')
                    ->schema([
                        TextInput::make('question')->required()->maxLength(500)->live(onBlur: true),
                        Textarea::make('answer')->rows(3)->required(),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->reorderable()
                    ->defaultItems(0)
                    ->itemLabel(fn (array $state): ?string => $state['question'] ?? null)
                    ->addActionLabel('Add FAQ')
                    ->columnSpanFull(),
                $key === 'tips' => Repeater::make($path)
                    ->label('Tips')
                    ->simple(TextInput::make('tip')->required()->maxLength(255))
                    ->reorderable()
                    ->defaultItems(0)
                    ->addActionLabel('Add tip')
                    ->columnSpanFull(),
                in_array($key, ['vision_body', 'mission_body', 'lead_text', 'hero_subtitle', 'team_intro', 'guides_intro'], true),
                str_ends_with($key, '_lead') => Textarea::make($path)->label($label)->rows(3)->columnSpanFull(),
                str_ends_with($key, '_body') => RichEditor::make($path)->label($label)->columnSpanFull(),
                default => TextInput::make($path)->label($label)->maxLength(500),
            };
        }

        return $fields;
    }
}
