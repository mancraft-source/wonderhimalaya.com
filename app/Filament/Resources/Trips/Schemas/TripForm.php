<?php

namespace App\Filament\Resources\Trips\Schemas;

use App\Enums\PublishStatus;
use App\Filament\Support\ContentEditorFields;
use App\Filament\Support\FormHelpers;
use App\Filament\Support\SeoFields;
use App\Models\Tag;
use App\Models\Tier;
use App\Models\Trip;
use App\Support\Content;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;

class TripForm
{
    public const CURRENCIES = ['USD' => 'USD', 'EUR' => 'EUR', 'GBP' => 'GBP', 'AUD' => 'AUD', 'NPR' => 'NPR'];

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Trip')
                    ->persistTabInQueryString()
                    ->columnSpanFull()
                    ->tabs([
                        self::basicsTab(),
                        self::detailsTab(),
                        self::overviewTab(),
                        self::itineraryTab(),
                        self::pricingTab(),
                        self::packingTab(),
                        self::faqsTab(),
                        self::mediaTab(),
                        Tab::make('SEO')
                            ->icon(Heroicon::OutlinedMagnifyingGlass)
                            ->schema([SeoFields::section(Trip::class)]),
                    ]),
            ]);
    }

    private static function basicsTab(): Tab
    {
        return Tab::make('Basics')
            ->icon(Heroicon::OutlinedInformationCircle)
            ->columns(2)
            ->schema([
                FormHelpers::titleWithSlug(),
                FormHelpers::slug(),
                TextInput::make('trip_code')
                    ->label('Trip code')
                    ->maxLength(50)
                    ->unique(ignoreRecord: true),
                TextInput::make('subtitle')->maxLength(255),
                Textarea::make('excerpt')
                    ->rows(3)
                    ->columnSpanFull()
                    ->helperText('Short summary used on trip cards and listings.'),
                Select::make('status')
                    ->options(PublishStatus::class)
                    ->default(PublishStatus::Draft)
                    ->required(),
                DateTimePicker::make('published_at')
                    ->native(false)
                    ->helperText('Leave blank to publish immediately when status is Published.'),
                Fieldset::make('Flags')
                    ->columnSpanFull()
                    ->columns(['default' => 2, 'md' => 3])
                    ->schema([
                        Toggle::make('is_featured')->label('Featured'),
                        Toggle::make('is_popular')->label('Popular'),
                        Toggle::make('is_top_selling')->label('Top selling'),
                        Toggle::make('is_trip_of_month')->label('Trip of the month'),
                        Toggle::make('is_fixed_departure')->label('Fixed departures (group trip)')->live(),
                    ]),
                Section::make('Departures')
                    ->columnSpanFull()
                    ->columns(['default' => 1, 'md' => 3])
                    ->schema([
                        Select::make('departure_mode')
                            ->label('How it departs')
                            ->options(['daily' => 'Every day (travellers pick a start date)', 'scheduled' => 'Scheduled dates only (Departures tab)'])
                            ->default('daily')
                            ->required(),
                        TextInput::make('departure_cutoff_days')
                            ->label('Bookings close (days before)')
                            ->numeric()->integer()->minValue(0)->default(10)->required(),
                        TextInput::make('departure_capacity')
                            ->label('Max group size per departure')
                            ->numeric()->integer()->minValue(1)->placeholder('e.g. 10'),
                    ]),
            ]);
    }

    private static function detailsTab(): Tab
    {
        return Tab::make('Details')
            ->icon(Heroicon::OutlinedAdjustmentsHorizontal)
            ->schema([
                Grid::make(['default' => 2, 'md' => 4])->schema([
                    TextInput::make('duration_days')->label('Days')->numeric()->minValue(1)->maxValue(365),
                    TextInput::make('duration_nights')->label('Nights')->numeric()->minValue(0)->maxValue(365),
                    TextInput::make('max_altitude_m')->label('Max altitude')->numeric()->suffix('m')->maxValue(9000),
                    TextInput::make('min_age')->label('Minimum age')->numeric()->maxValue(99),
                    TextInput::make('group_min')->label('Group min')->numeric()->minValue(1),
                    TextInput::make('group_max')->label('Group max')->numeric()->minValue(1)->gte('group_min'),
                ]),
                Grid::make(2)->schema([
                    TextInput::make('best_season')->maxLength(255),
                    TextInput::make('accommodation')->maxLength(255),
                    TextInput::make('meals')->maxLength(255),
                    TextInput::make('transport')->maxLength(255),
                    TextInput::make('start_point')->maxLength(255),
                    TextInput::make('end_point')->maxLength(255),
                ]),
                Section::make('Classification')
                    ->columns(2)
                    ->schema([
                        Select::make('difficulty_id')
                            ->relationship('difficulty', 'name')
                            ->preload(),
                        Select::make('destination_id')
                            ->label('Primary destination')
                            ->relationship('destination', 'name')
                            ->searchable()
                            ->preload(),
                        Select::make('destinations')
                            ->relationship('destinations', 'name')
                            ->multiple()
                            ->searchable()
                            ->preload(),
                        Select::make('activities')
                            ->relationship('activities', 'name')
                            ->multiple()
                            ->searchable()
                            ->preload(),
                        Select::make('tiers')
                            ->relationship('tiers', 'name')
                            ->multiple()
                            ->preload(),
                        Select::make('tags')
                            ->relationship('tags', 'name')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                TextInput::make('name')->required()->maxLength(255),
                            ])
                            ->createOptionUsing(fn (array $data): int => Tag::firstOrCreate(
                                ['slug' => Str::slug($data['name'])],
                                ['name' => $data['name']],
                            )->getKey()),
                    ]),
            ]);
    }

    private static function overviewTab(): Tab
    {
        return Tab::make('Overview & highlights')
            ->icon(Heroicon::OutlinedDocumentText)
            ->schema([
                RichEditor::make('overview')->columnSpanFull(),
                Repeater::make('highlights')
                    ->simple(TextInput::make('text')->required()->maxLength(500))
                    ->addActionLabel('Add highlight')
                    ->reorderable()
                    ->defaultItems(0),
                Grid::make(2)->schema([
                    Repeater::make('includes')
                        ->label('Cost includes')
                        ->simple(TextInput::make('text')->required()->maxLength(500))
                        ->addActionLabel('Add item')
                        ->defaultItems(0),
                    Repeater::make('excludes')
                        ->label('Cost excludes')
                        ->simple(TextInput::make('text')->required()->maxLength(500))
                        ->addActionLabel('Add item')
                        ->defaultItems(0),
                ]),
                KeyValue::make('facts')
                    ->label('Trip facts')
                    ->keyLabel('Fact')
                    ->valueLabel('Value')
                    ->reorderable(),
            ]);
    }

    private static function itineraryTab(): Tab
    {
        return Tab::make('Itinerary')
            ->icon(Heroicon::OutlinedCalendarDays)
            ->schema([
                Repeater::make('days')
                    ->hiddenLabel()
                    ->relationship('days')
                    ->orderColumn('sort')
                    ->collapsible()
                    ->collapsed(fn (string $operation): bool => $operation === 'edit')
                    ->cloneable()
                    ->defaultItems(0)
                    ->addActionLabel('Add day')
                    ->itemLabel(fn (array $state): string => 'Day '.($state['day_number'] ?? '?').' — '.($state['title'] ?? ''))
                    ->columns(4)
                    ->schema([
                        TextInput::make('day_number')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->live(onBlur: true),
                        TextInput::make('title')->required()->maxLength(500)->columnSpan(3)->live(onBlur: true),
                        RichEditor::make('description')->columnSpanFull(),
                        TextInput::make('altitude_m')->label('Altitude')->numeric()->suffix('m'),
                        TextInput::make('distance_km')->label('Distance')->numeric()->suffix('km'),
                        TextInput::make('duration')->placeholder('5–6 hrs')->maxLength(255),
                        TextInput::make('accommodation')->maxLength(255),
                        CheckboxList::make('meals')
                            ->options(['breakfast' => 'Breakfast', 'lunch' => 'Lunch', 'dinner' => 'Dinner'])
                            ->columns(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    private static function pricingTab(): Tab
    {
        return Tab::make('Group pricing')
            ->icon(Heroicon::OutlinedCurrencyDollar)
            ->schema([
                Grid::make(3)->schema([
                    TextInput::make('price_from')->label('Price from (pp)')->numeric()->minValue(0)
                        ->helperText('Headline "from" price on cards and listings.'),
                    TextInput::make('sale_price')->label('Sale price (pp)')->numeric()->minValue(0)
                        ->helperText('Shown instead of the regular price when set.'),
                    Select::make('currency')->options(self::CURRENCIES)->default('USD')->required(),
                ]),
                Repeater::make('prices')
                    ->label('Group pricing (per person)')
                    ->helperText("Add one row per package and group-size band, e.g. Standard · 1 pax · 1,900 / Standard · 2–5 pax · 1,585 / Standard · 6–12 pax · 1,450. Leave Pax max empty for 'and above'. The site shows the right per-person price for the chosen group size.")
                    ->relationship('prices')
                    ->orderColumn('sort')
                    ->defaultItems(0)
                    ->collapsible()
                    ->addActionLabel('Add price row')
                    ->itemLabel(fn (array $state, Get $get): string => self::priceRowLabel($state, $get('currency')))
                    ->columns(['default' => 2, 'md' => 6])
                    ->schema([
                        Select::make('tier_id')
                            ->label('Package')
                            ->options(fn () => self::tierOptions())
                            ->placeholder('Standard')
                            ->live()
                            ->columnSpan(['default' => 2, 'md' => 2]),
                        TextInput::make('label')
                            ->maxLength(255)
                            ->placeholder('Optional, e.g. 2–5 people')
                            ->columnSpan(['default' => 2, 'md' => 2]),
                        TextInput::make('pax_min')
                            ->label('Pax min')
                            ->numeric()
                            ->integer()
                            ->default(1)
                            ->minValue(1)
                            ->required()
                            ->live(onBlur: true),
                        TextInput::make('pax_max')
                            ->label('Pax max')
                            ->numeric()
                            ->integer()
                            ->placeholder('and above')
                            ->minValue(fn (Get $get): int => max(1, (int) $get('pax_min')))
                            ->validationMessages(['min' => 'Pax max must be at least Pax min.'])
                            ->live(onBlur: true),
                        TextInput::make('price')
                            ->label('Price (pp)')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->live(onBlur: true)
                            ->columnSpan(['default' => 1, 'md' => 3]),
                        TextInput::make('sale_price')
                            ->label('Sale price (pp)')
                            ->numeric()
                            ->minValue(0)
                            ->columnSpan(['default' => 1, 'md' => 2]),
                        TextInput::make('child_price')
                            ->label('Child price (pp)')
                            ->numeric()
                            ->minValue(0)
                            ->columnSpan(['default' => 1, 'md' => 2]),
                        Toggle::make('is_primary')
                            ->label('Main package')
                            ->helperText('Shown first')
                            ->inline(false)
                            ->columnSpan(['default' => 1, 'md' => 2]),
                        RichEditor::make('description')
                            ->label('What this package includes')
                            ->toolbarButtons(['bold', 'italic', 'bulletList', 'orderedList', 'link'])
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    /** @return array<int, string> */
    private static function tierOptions(): array
    {
        return once(fn (): array => Tier::query()->orderBy('sort')->pluck('name', 'id')->all());
    }

    /** e.g. "Standard · 2–5 pax · US$1,585" */
    private static function priceRowLabel(array $state, ?string $currency): string
    {
        $tier = self::tierOptions()[$state['tier_id'] ?? null] ?? 'Standard';
        $min = (int) ($state['pax_min'] ?? 1) ?: 1;
        $max = filled($state['pax_max'] ?? null) ? (int) $state['pax_max'] : null;

        $pax = match (true) {
            $max === null => "{$min}+ pax",
            $max === $min => "{$min} pax",
            default => "{$min}–{$max} pax",
        };

        $price = filled($state['price'] ?? null)
            ? ($currency === 'USD' || blank($currency) ? 'US$' : $currency.' ').number_format((float) $state['price'])
            : null;

        return implode(' · ', array_filter([$tier, $pax, $price]));
    }

    private static function packingTab(): Tab
    {
        return Tab::make('Packing & notes')
            ->icon(Heroicon::OutlinedBriefcase)
            ->schema([
                Textarea::make('itinerary_note')
                    ->label('Itinerary note')
                    ->rows(2)
                    ->placeholder(Content::tripPageDefaults()['itinerary_note'])
                    ->helperText('Leave empty to use the default note from Content → Trip page content.'),
                ContentEditorFields::packingGroups('packing')
                    ->label('Packing list')
                    ->helperText('Leave empty to use the default packing list (Content → Trip page content) for treks and climbs.')
                    ->hintAction(
                        Action::make('copyDefaultPacking')
                            ->label('Copy default packing list')
                            ->icon(Heroicon::OutlinedClipboardDocumentList)
                            ->requiresConfirmation(fn (Get $get): bool => filled($get('packing')))
                            ->modalDescription('This replaces the packing list below with the default list.')
                            ->action(function (Repeater $component): void {
                                $component->getRootContainer()->fillPartially(
                                    ['packing' => Content::tripPage()['packing_default']],
                                    ['packing'],
                                    shouldFillStateWithNull: false,
                                );
                            }),
                    ),
            ]);
    }

    private static function faqsTab(): Tab
    {
        return Tab::make('FAQs')
            ->icon(Heroicon::OutlinedQuestionMarkCircle)
            ->schema([
                Repeater::make('faqs')
                    ->hiddenLabel()
                    ->relationship('faqs')
                    ->orderColumn('sort')
                    ->collapsible()
                    ->defaultItems(0)
                    ->addActionLabel('Add FAQ')
                    ->itemLabel(fn (array $state): ?string => $state['question'] ?? null)
                    ->schema([
                        TextInput::make('question')->required()->maxLength(500)->live(onBlur: true),
                        RichEditor::make('answer')->required(),
                    ]),
            ]);
    }

    private static function mediaTab(): Tab
    {
        return Tab::make('Media')
            ->icon(Heroicon::OutlinedPhoto)
            ->columns(2)
            ->schema([
                SpatieMediaLibraryFileUpload::make('hero')
                    ->label('Hero image')
                    ->collection('hero')
                    ->image()
                    ->imageEditor()
                    ->maxSize(8192),
                SpatieMediaLibraryFileUpload::make('map')
                    ->label('Route map')
                    ->collection('map')
                    ->image()
                    ->maxSize(8192),
                SpatieMediaLibraryFileUpload::make('gallery')
                    ->collection('gallery')
                    ->image()
                    ->multiple()
                    ->reorderable()
                    ->panelLayout('grid')
                    ->maxSize(8192)
                    ->columnSpanFull(),
                Repeater::make('videos')
                    ->label('Videos (shown above the FAQs)')
                    ->helperText('YouTube or Vimeo links.')
                    ->simple(TextInput::make('url')->url()->required()->placeholder('https://www.youtube.com/watch?v=… or https://vimeo.com/…'))
                    ->addActionLabel('Add video URL')
                    ->defaultItems(0)
                    ->columnSpanFull(),
            ]);
    }
}
