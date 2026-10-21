<?php

namespace App\Filament\Pages;

use App\Filament\Support\ContentEditorFields;
use App\Models\Activity;
use App\Support\Content;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * Homepage copy, stored in Setting "home" (shape: Content::homeDefaults()).
 */
class ManageHomepage extends ContentSettingsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    protected static ?string $navigationLabel = 'Homepage';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Homepage content';

    protected static ?string $slug = 'content/homepage';

    protected static function settingKey(): string
    {
        return 'home';
    }

    protected static function contentDefaults(): array
    {
        return Content::homeDefaults();
    }

    protected static function currentContent(): array
    {
        return Content::home();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('viewHomepage')
                ->label('View homepage')
                ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->color('gray')
                ->url(url('/'))
                ->openUrlInNewTab(),
            $this->resetAction(),
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Tabs::make('Homepage')
                    ->persistTabInQueryString()
                    ->tabs([
                        self::heroTab(),
                        Tab::make('Ticker')
                            ->icon(Heroicon::OutlinedMegaphone)
                            ->schema([
                                TagsInput::make('ticker')
                                    ->label('Ticker items')
                                    ->placeholder('e.g. Everest · Annapurna · Langtang')
                                    ->helperText('Press Enter after each item. Leave empty to show regions & trust signals automatically.')
                                    ->reorderable(),
                            ]),
                        self::introTab(),
                        self::moodsTab(),
                        self::signatureTab(),
                        Tab::make('Feature trip')
                            ->icon(Heroicon::OutlinedStar)
                            ->schema([
                                Select::make('feature_trip_id')
                                    ->label('Featured trip')
                                    ->options(fn (): array => ContentEditorFields::publishedTripOptions())
                                    ->searchable()
                                    ->helperText('Leave empty to show the trip of the month, else the top-selling trip.'),
                            ]),
                        Tab::make('Section headings')
                            ->icon(Heroicon::OutlinedQueueList)
                            ->schema([
                                self::headingSection('Regions', 'regions', intro: true),
                                self::headingSection('Fixed departures', 'departures', intro: true),
                                self::headingSection('Reviews', 'reviews'),
                                self::headingSection('Journal', 'journal'),
                            ]),
                        self::whyTab(),
                    ]),
            ]);
    }

    private static function heroTab(): Tab
    {
        return Tab::make('Hero')
            ->icon(Heroicon::OutlinedPhoto)
            ->columns(2)
            ->schema([
                TextInput::make('hero_eyebrow')->label('Eyebrow')->maxLength(255),
                TextInput::make('hero_title')->label('Title')->required()->maxLength(255),
                TextInput::make('hero_title_em')
                    ->label('Title — italic accent words')
                    ->helperText('Shown in italics after the title.')
                    ->maxLength(255),
                Textarea::make('hero_subtitle')->label('Subtitle')->rows(3)->columnSpanFull(),
                Repeater::make('hero_slides')
                    ->label('Slides')
                    ->helperText('Leave empty for automatic curated slides.')
                    ->schema([
                        Select::make('trip_id')
                            ->label('Trip')
                            ->options(fn (): array => ContentEditorFields::publishedTripOptions())
                            ->searchable()
                            ->required(),
                        TextInput::make('eyebrow')->maxLength(255),
                        TextInput::make('title')->maxLength(255)->live(onBlur: true),
                        ContentEditorFields::image('image', 'content/home')
                            ->helperText("Leave image empty to use the trip's hero photo."),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->reorderable()
                    ->defaultItems(0)
                    ->maxItems(8)
                    ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                    ->addActionLabel('Add slide')
                    ->columnSpanFull(),
            ]);
    }

    private static function introTab(): Tab
    {
        return Tab::make('Intro')
            ->icon(Heroicon::OutlinedHandRaised)
            ->columns(2)
            ->schema([
                TextInput::make('intro_eyebrow')->label('Eyebrow')->maxLength(255),
                TextInput::make('intro_badge')
                    ->label('Rotating badge text')
                    ->helperText('Runs around the circular badge; end with a separator, e.g. " · ".')
                    ->maxLength(255),
                TextInput::make('intro_title')->label('Title')->maxLength(255)->columnSpanFull(),
                Textarea::make('intro_lead')->label('Lead')->rows(3)->columnSpanFull(),
                Textarea::make('intro_body')->label('Body')->rows(4)->columnSpanFull(),
                Select::make('collage_trip_ids')
                    ->label('Collage trips')
                    ->multiple()
                    ->maxItems(3)
                    ->options(fn (): array => ContentEditorFields::publishedTripOptions(withHeroImage: true))
                    ->searchable()
                    ->helperText('Up to 3 trips with a hero image. Leave empty to pick automatically.')
                    ->columnSpanFull(),
            ]);
    }

    private static function moodsTab(): Tab
    {
        return Tab::make('Travel by feeling')
            ->icon(Heroicon::OutlinedHeart)
            ->columns(2)
            ->schema([
                TextInput::make('moods_eyebrow')->label('Eyebrow')->maxLength(255),
                TextInput::make('moods_title')->label('Title')->maxLength(255),
                Repeater::make('moods')
                    ->label('Mood panels')
                    ->helperText('Leave empty for the default panels.')
                    ->schema([
                        Select::make('activity_id')
                            ->label('Activity')
                            ->options(fn (): array => Activity::query()->orderBy('name')->pluck('name', 'id')->all())
                            ->searchable()
                            ->required(),
                        TextInput::make('title')->required()->maxLength(255)->live(onBlur: true),
                        TextInput::make('line')->label('Short line')->maxLength(255)->columnSpanFull(),
                        ContentEditorFields::image('image', 'content/home')
                            ->helperText("Leave empty to use the activity's image.")
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->reorderable()
                    ->defaultItems(0)
                    ->maxItems(6)
                    ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                    ->addActionLabel('Add mood')
                    ->columnSpanFull(),
            ]);
    }

    private static function signatureTab(): Tab
    {
        return Tab::make('Signature journeys')
            ->icon(Heroicon::OutlinedMap)
            ->columns(2)
            ->schema([
                TextInput::make('signature_eyebrow')->label('Eyebrow')->maxLength(255),
                TextInput::make('signature_title')->label('Title')->maxLength(255),
                Textarea::make('signature_intro')->label('Intro')->rows(2)->columnSpanFull(),
                Select::make('signature_trip_ids')
                    ->label('Trips')
                    ->multiple()
                    ->options(fn (): array => ContentEditorFields::publishedTripOptions())
                    ->searchable()
                    ->helperText('Shown in the order selected. Leave empty for featured / popular trips automatically.')
                    ->columnSpanFull(),
            ]);
    }

    private static function whyTab(): Tab
    {
        return Tab::make('Why us')
            ->icon(Heroicon::OutlinedTrophy)
            ->columns(2)
            ->schema([
                TextInput::make('why_eyebrow')->label('Eyebrow')->maxLength(255),
                TextInput::make('why_title')->label('Title')->maxLength(255),
                Textarea::make('why_award_text')->label('Award text')->rows(2)->columnSpanFull(),
                ContentEditorFields::iconTiles('why_tiles', maxItems: 4)
                    ->label('Tiles')
                    ->columnSpanFull(),
            ]);
    }

    private static function headingSection(string $label, string $prefix, bool $intro = false): Section
    {
        return Section::make($label)
            ->columns(2)
            ->compact()
            ->schema(array_filter([
                TextInput::make("{$prefix}_eyebrow")->label('Eyebrow')->maxLength(255),
                TextInput::make("{$prefix}_title")->label('Title')->maxLength(255),
                $intro ? Textarea::make("{$prefix}_intro")->label('Intro')->rows(2)->columnSpanFull() : null,
            ]));
    }
}
