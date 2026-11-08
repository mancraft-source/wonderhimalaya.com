<?php

namespace App\Filament\Pages;

use App\Console\Commands\SeoScore;
use App\Filament\Support\SeoFields;
use App\Models\Setting;
use App\Support\Permissions;
use App\Support\Seo\SeoSettings;
use App\Support\Seo\SeoTemplate;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Cache;
use UnitEnum;

/**
 * SEO → SEO settings: title/description templates per content type (Yoast-style %%variables%%),
 * search-engine visibility, sitemap inclusion, social defaults, webmaster verification and robots.txt.
 *
 * @property-read Schema $form
 */
class ManageSeoSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    protected static string|UnitEnum|null $navigationGroup = 'SEO';

    protected static ?string $navigationLabel = 'SEO settings';

    protected static ?int $navigationSort = 10;

    protected static ?string $title = 'SEO settings';

    protected static ?string $slug = 'seo-settings';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->can(Permissions::SETTINGS) ?? false;
    }

    public function mount(): void
    {
        $this->form->fill(SeoSettings::all());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Tabs::make('SEO settings')
                    ->persistTabInQueryString()
                    ->tabs([
                        Tab::make('Titles & meta')
                            ->icon(Heroicon::OutlinedDocumentText)
                            ->schema([
                                Section::make('General')
                                    ->columns(3)
                                    ->schema([
                                        TextInput::make('site_name')->label('Site name')->required()->helperText('%%sitename%%'),
                                        Select::make('separator')
                                            ->label('Title separator')
                                            ->options(['|' => '|', '–' => '–', '—' => '—', '·' => '·', '•' => '•', '/' => '/', '›' => '›'])
                                            ->selectablePlaceholder(false)
                                            ->live()
                                            ->helperText('%%sep%%'),
                                        Select::make('organization_type')
                                            ->label('Business type (schema)')
                                            ->options(['TravelAgency' => 'Travel agency', 'TouristInformationCenter' => 'Tourist information centre', 'LocalBusiness' => 'Local business', 'Organization' => 'Organisation'])
                                            ->selectablePlaceholder(false),
                                    ]),
                                Section::make('Homepage')
                                    ->schema([
                                        TextInput::make('home_title')->label('Homepage SEO title')->maxLength(120)->required()
                                            ->hint(fn (?string $state): string => mb_strlen((string) $state).' / 60'),
                                        Textarea::make('home_description')->label('Homepage meta description')->rows(2)->maxLength(320)
                                            ->hint(fn (?string $state): string => mb_strlen((string) $state).' / 160'),
                                    ]),
                                ...collect(SeoSettings::TYPES)->map(fn (string $label, string $type) => Section::make($label)
                                    ->description('Used whenever a record leaves its own SEO title or description blank.')
                                    ->collapsible()
                                    ->collapsed($type !== 'trip')
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make("types.{$type}.title")->label('SEO title template')->required()->live(debounce: 600)->columnSpanFull()
                                            ->helperText(SeoFields::variablesHelp($type))
                                            ->hint(fn (?string $state, Get $get): string => 'e.g. '.SeoTemplate::render($state, self::sampleVariables($type))),
                                        TextInput::make("types.{$type}.description")->label('Meta description template')->columnSpanFull(),
                                        Toggle::make("types.{$type}.noindex")->label('Hide all '.strtolower($label).' from search engines'),
                                        Toggle::make("types.{$type}.sitemap")->label('Include in the XML sitemap'),
                                    ]))->values()->all(),
                            ]),
                        Tab::make('Social sharing')
                            ->icon(Heroicon::OutlinedShare)
                            ->schema([
                                FileUpload::make('default_image')
                                    ->label('Default share image')
                                    ->helperText('Used when a page has no photo or social image of its own. 1200 × 630 px.')
                                    ->image()
                                    ->disk('public')
                                    ->directory('seo')
                                    ->visibility('public')
                                    ->maxSize(4096)
                                    ->imageEditor(),
                                TextInput::make('twitter_handle')->label('X (Twitter) username')->placeholder('@WonderHimalaya')->prefixIcon(Heroicon::OutlinedAtSymbol),
                            ]),
                        Tab::make('Webmaster tools')
                            ->icon(Heroicon::OutlinedShieldCheck)
                            ->columns(2)
                            ->schema([
                                TextInput::make('verification.google')->label('Google Search Console')->helperText('The content="…" value of the HTML-tag verification method.'),
                                TextInput::make('verification.bing')->label('Bing Webmaster Tools'),
                                TextInput::make('verification.yandex')->label('Yandex'),
                                TextInput::make('verification.pinterest')->label('Pinterest'),
                                TextInput::make('verification.facebook')->label('Facebook domain verification'),
                            ]),
                        Tab::make('Indexing & robots.txt')
                            ->icon(Heroicon::OutlinedEye)
                            ->schema([
                                Toggle::make('allow_indexing')
                                    ->label('Allow search engines to index the site')
                                    ->helperText(app()->isProduction()
                                        ? 'Turning this off adds noindex to every page and blocks crawlers in robots.txt.'
                                        : 'This is not the production environment, so the site is always hidden from search engines here.'),
                                Textarea::make('robots_extra')
                                    ->label('Extra robots.txt rules')
                                    ->rows(5)
                                    ->placeholder("Disallow: /private-offer\nUser-agent: GPTBot\nDisallow: /")
                                    ->helperText('Appended to the built-in rules (admin, checkout and search are already blocked; the sitemap is listed automatically).'),
                            ]),
                    ]),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')->label('Save SEO settings')->submit('save')->keyBindings(['mod+s']),
                        Action::make('sitemap')->label('View sitemap')->color('gray')->url(url('/sitemap.xml'), shouldOpenInNewTab: true),
                        Action::make('robots')->label('View robots.txt')->color('gray')->url(url('/robots.txt'), shouldOpenInNewTab: true),
                    ])->key('form-actions'),
                ]),
        ]);
    }

    public function save(): void
    {
        abort_unless(static::canAccess(), 403);

        $state = $this->form->getState();
        $state['default_image'] = is_array($state['default_image'] ?? null) ? (reset($state['default_image']) ?: null) : ($state['default_image'] ?? null);

        Setting::put('seo', array_replace_recursive(SeoSettings::DEFAULTS, $state));
        Cache::forget('sitemap.xml');
        $scored = SeoScore::recalculate();

        Notification::make()
            ->title('SEO settings saved')
            ->body("Titles, sitemap and robots.txt now use the new settings. {$scored} SEO scores refreshed.")
            ->success()
            ->send();
    }

    /** @return array<string, string> */
    private static function sampleVariables(string $type): array
    {
        return match ($type) {
            'trip' => ['title' => 'Everest Base Camp Trek', 'duration' => '14 Days', 'price' => 'US$1,585', 'destination' => 'Everest', 'difficulty' => 'Hard', 'max_altitude' => '5,364 m', 'trip_code' => 'WH-EBC'],
            'destination' => ['title' => 'Annapurna Region', 'parent' => 'Nepal', 'tagline' => 'Scenic trails, rich culture'],
            'activity' => ['title' => 'Peak Climbing'],
            'post' => ['title' => 'Best time to trek in Nepal', 'category' => 'Travel tips', 'author' => 'Niranjan Nepal', 'date' => now()->format('j F Y')],
            'team_member' => ['title' => 'Niranjan Nepal', 'role' => 'Managing Director'],
            default => ['title' => 'About us', 'parent' => 'Company'],
        };
    }
}
