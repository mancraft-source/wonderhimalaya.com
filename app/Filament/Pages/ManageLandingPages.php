<?php

namespace App\Filament\Pages;

use App\Filament\Support\ContentEditorFields;
use App\Models\Setting;
use App\Support\Content;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * Copy for the route-based landing pages. Each tab is one Setting row (b2b, tailor_made, contact)
 * shaped like Content::landingDefaults($key).
 */
class ManageLandingPages extends ContentSettingsPage
{
    public const KEYS = ['b2b', 'tailor_made', 'contact'];

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    protected static ?string $navigationLabel = 'Landing pages';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Landing pages';

    protected static ?string $slug = 'content/landing-pages';

    protected static function settingKey(): string
    {
        return 'b2b';
    }

    protected static function contentDefaults(): array
    {
        return collect(self::KEYS)->mapWithKeys(fn (string $key): array => [$key => Content::landingDefaults($key)])->all();
    }

    protected static function currentContent(): array
    {
        return collect(self::KEYS)->mapWithKeys(fn (string $key): array => [$key => Content::landing($key)])->all();
    }

    public function save(): void
    {
        abort_unless(static::canAccess(), 403);

        $state = $this->form->getState();

        foreach (self::KEYS as $key) {
            // Merge over the stored row so keys owned elsewhere (e.g. importers) survive.
            $stored = array_diff_key((array) Setting::get($key, []), Content::landingDefaults($key));
            Setting::put($key, $stored + Content::overrides($state[$key] ?? [], Content::landingDefaults($key)));
        }

        Notification::make()->title('Content saved')->success()->send();
    }

    protected function resetAction(): Action
    {
        return parent::resetAction()
            ->modalDescription('All landing-page edits (B2B, tailor-made, contact) are discarded and the launch copy is restored. This cannot be undone.')
            ->action(function (): void {
                abort_unless(static::canAccess(), 403);

                foreach (self::KEYS as $key) {
                    $row = Setting::query()->where('key', $key)->first();

                    if (! $row) {
                        continue;
                    }

                    $rest = array_diff_key((array) $row->value, Content::landingDefaults($key));

                    $rest === [] ? $row->delete() : Setting::put($key, $rest);
                }

                $this->form->fill(static::currentContent());

                Notification::make()->title('Restored default content')->success()->send();
            });
    }

    protected function getHeaderActions(): array
    {
        return [$this->resetAction()];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Tabs::make('Landing pages')
                    ->persistTabInQueryString()
                    ->tabs([
                        self::b2bTab(),
                        self::tailorMadeTab(),
                        self::contactTab(),
                    ]),
            ]);
    }

    private static function b2bTab(): Tab
    {
        return Tab::make('B2B')
            ->icon(Heroicon::OutlinedBriefcase)
            ->schema([
                Section::make('Hero')
                    ->columns(2)
                    ->schema([
                        TextInput::make('b2b.hero_eyebrow')->label('Eyebrow')->maxLength(255),
                        TextInput::make('b2b.hero_title')->label('Title')->maxLength(255),
                        TextInput::make('b2b.hero_title_em')->label('Title — italic accent words')->maxLength(255),
                        Textarea::make('b2b.hero_subtitle')->label('Subtitle')->rows(3)->columnSpanFull(),
                        TagsInput::make('b2b.credentials')
                            ->label('Credentials')
                            ->helperText('Badges under the hero. Press Enter after each one.')
                            ->reorderable()
                            ->columnSpanFull(),
                    ]),
                Section::make('Services')
                    ->columns(2)
                    ->schema([
                        TextInput::make('b2b.services_eyebrow')->label('Eyebrow')->maxLength(255),
                        TextInput::make('b2b.services_title')->label('Title')->maxLength(255),
                        Textarea::make('b2b.services_intro')->label('Intro')->rows(2)->columnSpanFull(),
                        ContentEditorFields::iconTiles('b2b.services', maxItems: 4)->label('Services')->columnSpanFull(),
                    ]),
                Section::make('Destinations')
                    ->columns(2)
                    ->schema([
                        TextInput::make('b2b.destinations_eyebrow')->label('Eyebrow')->maxLength(255),
                        TextInput::make('b2b.destinations_title')->label('Title')->maxLength(255),
                    ]),
                Section::make('Operational integrity')
                    ->columns(2)
                    ->schema([
                        TextInput::make('b2b.integrity_eyebrow')->label('Eyebrow')->maxLength(255),
                        TextInput::make('b2b.integrity_title')->label('Title')->maxLength(255),
                        Repeater::make('b2b.integrity')
                            ->label('Figures')
                            ->helperText('Up to two figures; the site-wide stats (Settings → Site settings) are appended automatically.')
                            ->schema([
                                TextInput::make('value')->required()->maxLength(50)->placeholder('24h'),
                                TextInput::make('label')->required()->maxLength(255)->placeholder('Quote turnaround'),
                            ])
                            ->columns(2)
                            ->reorderable()
                            ->defaultItems(0)
                            ->maxItems(2)
                            ->addActionLabel('Add figure')
                            ->columnSpanFull(),
                    ]),
                Section::make('Partnership form')
                    ->columns(2)
                    ->schema([
                        TextInput::make('b2b.form_eyebrow')->label('Eyebrow')->maxLength(255),
                        TextInput::make('b2b.form_title')->label('Title')->maxLength(255),
                        Textarea::make('b2b.form_intro')->label('Intro')->rows(2)->columnSpanFull(),
                    ]),
            ]);
    }

    private static function tailorMadeTab(): Tab
    {
        return Tab::make('Tailor-made')
            ->icon(Heroicon::OutlinedAdjustmentsHorizontal)
            ->columns(2)
            ->schema([
                TextInput::make('tailor_made.eyebrow')->label('Eyebrow')->maxLength(255),
                TextInput::make('tailor_made.title')->label('Title')->maxLength(255),
                Textarea::make('tailor_made.intro')->label('Intro')->rows(3)->columnSpanFull(),
                Repeater::make('tailor_made.benefits')
                    ->label('Benefits')
                    ->schema([
                        Select::make('icon')->options(Content::ICONS)->searchable()->required(),
                        TextInput::make('text')->required()->maxLength(255),
                    ])
                    ->columns(2)
                    ->reorderable()
                    ->defaultItems(0)
                    ->maxItems(6)
                    ->addActionLabel('Add benefit')
                    ->columnSpanFull(),
            ]);
    }

    private static function contactTab(): Tab
    {
        return Tab::make('Contact')
            ->icon(Heroicon::OutlinedEnvelope)
            ->columns(2)
            ->schema([
                TextInput::make('contact.eyebrow')->label('Eyebrow')->maxLength(255),
                TextInput::make('contact.title')->label('Title')->maxLength(255),
                TextInput::make('contact.title_em')->label('Title — italic accent words')->maxLength(255),
                TextInput::make('contact.map_query')
                    ->label('Map location')
                    ->helperText('Address or place name used for the embedded map.')
                    ->maxLength(255),
                Textarea::make('contact.intro')->label('Intro')->rows(3)->columnSpanFull(),
            ]);
    }
}
