<?php

namespace App\Filament\Resources\Trips\Schemas;

use App\Enums\PublishStatus;
use App\Filament\Support\FormHelpers;
use App\Models\Tag;
use App\Models\Tier;
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
                        self::faqsTab(),
                        self::mediaTab(),
                        Tab::make('SEO')
                            ->icon(Heroicon::OutlinedMagnifyingGlass)
                            ->schema([FormHelpers::seoSection()]),
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
                        Toggle::make('is_fixed_departure')->label('Fixed departures'),
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
        return Tab::make('Pricing')
            ->icon(Heroicon::OutlinedCurrencyDollar)
            ->schema([
                Grid::make(3)->schema([
                    TextInput::make('price_from')->label('Price from (pp)')->numeric()->minValue(0),
                    TextInput::make('sale_price')->label('Sale price (pp)')->numeric()->minValue(0)
                        ->helperText('Shown instead of the regular price when set.'),
                    Select::make('currency')->options(self::CURRENCIES)->default('USD')->required(),
                ]),
                Repeater::make('prices')
                    ->label('Price bands')
                    ->relationship('prices')
                    ->orderColumn('sort')
                    ->defaultItems(0)
                    ->addActionLabel('Add price band')
                    ->columns(6)
                    ->schema([
                        Select::make('tier_id')
                            ->label('Tier')
                            ->options(fn () => Tier::query()->orderBy('sort')->pluck('name', 'id')),
                        TextInput::make('label')->maxLength(255)->placeholder('e.g. 2–5 people'),
                        TextInput::make('pax_min')->label('Pax min')->numeric()->default(1)->minValue(1)->required(),
                        TextInput::make('pax_max')->label('Pax max')->numeric()->minValue(1),
                        TextInput::make('price')->numeric()->required()->minValue(0),
                        TextInput::make('sale_price')->numeric()->minValue(0),
                    ]),
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
                    ->simple(TextInput::make('url')->url()->required()->placeholder('https://www.youtube.com/watch?v=…'))
                    ->addActionLabel('Add video URL')
                    ->defaultItems(0)
                    ->columnSpanFull(),
            ]);
    }
}
