<?php

namespace App\Filament\Resources\Pages\Schemas;

use App\Enums\PublishStatus;
use App\Filament\Support\ContentFields;
use App\Models\Activity;
use App\Models\Destination;
use App\Models\Page;
use App\Models\Trip;
use Closure;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

class PageForm
{
    public const TEMPLATES = [
        'default' => 'Default',
        'landing' => 'Landing',
        'guide' => 'Travel guide',
        'legal' => 'Legal',
    ];

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Group::make([
                    Section::make('Page')
                        ->columns(2)
                        ->schema([
                            ContentFields::title()->columnSpanFull(),
                            ContentFields::slug(unique: false)
                                ->rule(fn (Get $get, ?Page $record): Closure => self::uniquePathRule($get, $record)),
                            Select::make('parent_id')
                                ->label('Parent page')
                                ->relationship('parent', 'title', ignoreRecord: true)
                                ->searchable()
                                ->preload(),
                            Textarea::make('excerpt')
                                ->rows(3)
                                ->columnSpanFull(),
                        ]),
                    Section::make('Content blocks')
                        ->schema([self::blocks()]),
                    ContentFields::seoSection(),
                ])->columnSpan(['lg' => 2]),

                Group::make([
                    Section::make('Publishing')
                        ->schema([
                            Select::make('status')
                                ->options(PublishStatus::class)
                                ->default(PublishStatus::Draft)
                                ->required(),
                            DateTimePicker::make('published_at')
                                ->label('Publish date'),
                            Select::make('template')
                                ->options(self::TEMPLATES)
                                ->default('default')
                                ->required(),
                            Toggle::make('show_in_footer'),
                            TextInput::make('sort')
                                ->numeric()
                                ->default(0)
                                ->required(),
                        ]),
                    Section::make('Hero image')
                        ->schema([
                            SpatieMediaLibraryFileUpload::make('hero')
                                ->collection('hero')
                                ->hiddenLabel()
                                ->image()
                                ->imageEditor(),
                        ]),
                ])->columnSpan(['lg' => 1]),
            ]);
    }

    /** The page's full path must be unique (slug + parent chain). */
    protected static function uniquePathRule(Get $get, ?Page $record): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($get, $record): void {
            $page = new Page(['slug' => $value, 'parent_id' => $get('parent_id') ?: null]);
            $path = $page->buildPath();

            $exists = Page::withTrashed()
                ->where('path', $path)
                ->when($record?->getKey(), fn (EloquentBuilder $q, $id) => $q->whereKeyNot($id))
                ->exists();

            if ($exists) {
                $fail("Another page already uses the path /{$path}.");
            }
        };
    }

    public static function blocks(): Builder
    {
        return Builder::make('blocks')
            ->hiddenLabel()
            ->columnSpanFull()
            ->collapsible()
            ->cloneable()
            ->blockNumbers(false)
            ->addActionLabel('Add block')
            ->blocks([
                Block::make('hero')
                    ->icon(Heroicon::OutlinedPhoto)
                    ->label(self::label('Hero', 'heading'))
                    ->columns(2)
                    ->schema([
                        TextInput::make('eyebrow'),
                        TextInput::make('heading')->required()->live(onBlur: true),
                        Textarea::make('subheading')->rows(2)->columnSpanFull(),
                        self::image('image')->columnSpanFull(),
                        TextInput::make('cta_label')->label('Button label'),
                        TextInput::make('cta_url')->label('Button URL'),
                    ]),

                Block::make('rich_text')
                    ->icon(Heroicon::OutlinedDocumentText)
                    ->label('Rich text')
                    ->schema([
                        RichEditor::make('content')->required(),
                    ]),

                Block::make('image_text')
                    ->icon(Heroicon::OutlinedNewspaper)
                    ->label(self::label('Image + text', 'heading'))
                    ->columns(2)
                    ->schema([
                        TextInput::make('heading')->live(onBlur: true)->columnSpanFull(),
                        RichEditor::make('content')->columnSpanFull(),
                        self::image('image'),
                        Select::make('image_position')
                            ->options(['left' => 'Left', 'right' => 'Right'])
                            ->default('left')
                            ->selectablePlaceholder(false),
                        TextInput::make('cta_label')->label('Button label'),
                        TextInput::make('cta_url')->label('Button URL'),
                    ]),

                Block::make('trip_grid')
                    ->icon(Heroicon::OutlinedMap)
                    ->label(self::label('Trip grid', 'heading'))
                    ->columns(2)
                    ->schema([
                        TextInput::make('heading')->live(onBlur: true),
                        TextInput::make('limit')->numeric()->minValue(1)->maxValue(24)->default(6),
                        Textarea::make('intro')->rows(2)->columnSpanFull(),
                        Select::make('source')
                            ->options([
                                'featured' => 'Featured trips',
                                'popular' => 'Popular trips',
                                'top_selling' => 'Top selling trips',
                                'destination' => 'Trips in a destination',
                                'activity' => 'Trips of an activity',
                                'manual' => 'Pick trips manually',
                            ])
                            ->default('featured')
                            ->required()
                            ->live(),
                        Select::make('destination_id')
                            ->label('Destination')
                            ->options(fn () => Destination::query()->orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->visible(fn (Get $get): bool => $get('source') === 'destination')
                            ->required(fn (Get $get): bool => $get('source') === 'destination'),
                        Select::make('activity_id')
                            ->label('Activity')
                            ->options(fn () => Activity::query()->orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->visible(fn (Get $get): bool => $get('source') === 'activity')
                            ->required(fn (Get $get): bool => $get('source') === 'activity'),
                        Select::make('trip_ids')
                            ->label('Trips')
                            ->multiple()
                            ->options(fn () => Trip::query()->orderBy('title')->pluck('title', 'id'))
                            ->searchable()
                            ->visible(fn (Get $get): bool => $get('source') === 'manual')
                            ->columnSpanFull(),
                    ]),

                Block::make('destination_grid')
                    ->icon(Heroicon::OutlinedGlobeAsiaAustralia)
                    ->label(self::label('Destination grid', 'heading'))
                    ->schema([
                        TextInput::make('heading')->live(onBlur: true),
                        Select::make('destination_ids')
                            ->label('Destinations')
                            ->helperText('Leave empty to show all top-level destinations.')
                            ->multiple()
                            ->options(fn () => Destination::query()->orderBy('name')->pluck('name', 'id'))
                            ->searchable(),
                    ]),

                Block::make('testimonials')
                    ->icon(Heroicon::OutlinedChatBubbleLeftRight)
                    ->label(self::label('Testimonials', 'heading'))
                    ->columns(2)
                    ->schema([
                        TextInput::make('heading')->live(onBlur: true),
                        TextInput::make('limit')->numeric()->minValue(1)->maxValue(24)->default(6),
                    ]),

                Block::make('stats')
                    ->icon(Heroicon::OutlinedChartBar)
                    ->label(self::label('Stats', 'heading'))
                    ->schema([
                        TextInput::make('heading')->live(onBlur: true),
                        Toggle::make('use_site_stats')
                            ->label('Use the site-wide stats from Site settings')
                            ->default(true)
                            ->live(),
                        Repeater::make('items')
                            ->hidden(fn (Get $get): bool => (bool) $get('use_site_stats'))
                            ->columns(2)
                            ->schema([
                                TextInput::make('value')->required(),
                                TextInput::make('label')->required(),
                            ])
                            ->defaultItems(0)
                            ->reorderable(),
                    ]),

                Block::make('faq')
                    ->icon(Heroicon::OutlinedQuestionMarkCircle)
                    ->label(self::label('FAQ', 'heading'))
                    ->schema([
                        TextInput::make('heading')->live(onBlur: true),
                        Repeater::make('items')
                            ->schema([
                                TextInput::make('question')->required()->live(onBlur: true),
                                Textarea::make('answer')->rows(3)->required(),
                            ])
                            ->itemLabel(fn (array $state): ?string => $state['question'] ?? null)
                            ->collapsible()
                            ->reorderable(),
                    ]),

                Block::make('cta')
                    ->icon(Heroicon::OutlinedMegaphone)
                    ->label(self::label('Call to action', 'heading'))
                    ->columns(2)
                    ->schema([
                        TextInput::make('heading')->required()->live(onBlur: true)->columnSpanFull(),
                        Textarea::make('text')->rows(2)->columnSpanFull(),
                        TextInput::make('button_label'),
                        TextInput::make('button_url'),
                        Select::make('style')
                            ->options(['primary' => 'Primary', 'dark' => 'Dark', 'image' => 'Image background'])
                            ->default('primary')
                            ->selectablePlaceholder(false),
                    ]),

                Block::make('gallery')
                    ->icon(Heroicon::OutlinedSquares2x2)
                    ->label('Gallery')
                    ->schema([
                        self::image('images')
                            ->multiple()
                            ->reorderable()
                            ->panelLayout('grid'),
                    ]),

                Block::make('team_grid')
                    ->icon(Heroicon::OutlinedUserGroup)
                    ->label(self::label('Team grid', 'heading'))
                    ->schema([
                        TextInput::make('heading')->live(onBlur: true),
                        Toggle::make('guides_only')->label('Only show guides'),
                    ]),

                Block::make('contact_form')
                    ->icon(Heroicon::OutlinedEnvelope)
                    ->label(self::label('Contact form', 'heading'))
                    ->schema([
                        TextInput::make('heading')->live(onBlur: true),
                        Textarea::make('intro')->rows(2),
                    ]),

                Block::make('enquiry_cta')
                    ->icon(Heroicon::OutlinedPaperAirplane)
                    ->label(self::label('Enquiry CTA', 'heading'))
                    ->schema([
                        TextInput::make('heading')->live(onBlur: true),
                        Textarea::make('text')->rows(2),
                    ]),
            ]);
    }

    protected static function image(string $name): FileUpload
    {
        return FileUpload::make($name)
            ->image()
            ->disk('public')
            ->directory('pages')
            ->visibility('public')
            ->maxSize(8192);
    }

    /** Block label: plain name in the picker, "Name — heading" on items. */
    protected static function label(string $name, string $key): Closure
    {
        return function (?array $state) use ($name, $key): string {
            if ($state === null || blank($state[$key] ?? null)) {
                return $name;
            }

            return $name.' — '.$state[$key];
        };
    }
}
