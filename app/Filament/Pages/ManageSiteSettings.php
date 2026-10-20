<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Support\Permissions;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Site-wide settings. Each top-level key (company, social, stats, …) is one
 * row in the `settings` table holding a JSON value; read with Setting::get('company.name').
 *
 * @property-read Schema $form
 */
class ManageSiteSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Site settings';

    protected static ?int $navigationSort = 10;

    protected static ?string $title = 'Site settings';

    protected static ?string $slug = 'site-settings';

    /** Shape (and defaults) of every settings key this page manages. */
    public const DEFAULTS = [
        'company' => [
            'name' => null, 'legal_name' => null, 'tagline' => null, 'phone' => null,
            'whatsapp' => null, 'email' => null, 'address' => null, 'hours' => null, 'registration' => null,
        ],
        'social' => [
            'facebook' => null, 'instagram' => null, 'tripadvisor' => null,
            'tiktok' => null, 'youtube' => null, 'twitter' => null,
        ],
        'stats' => [],
        'reviews' => ['google_rating' => null, 'google_label' => null, 'google_count' => null, 'google_url' => null, 'tripadvisor_award' => null],
        'b2b' => ['credentials' => []],
        'booking' => ['deposit_percent' => 20, 'balance_due_days' => 30, 'currency' => 'USD'],
        'notifications' => ['sales_email' => null],
        'analytics' => ['gtm_id' => null, 'ga4_id' => null],
        'integrations' => ['mailchimp_list_id' => null, 'turnstile_site_key' => null],
    ];

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->can(Permissions::SETTINGS) ?? false;
    }

    public function mount(): void
    {
        $data = [];

        foreach (self::DEFAULTS as $key => $default) {
            $value = Setting::get($key, $default);

            $data[$key] = ($key === 'stats')
                ? array_values(is_array($value) ? $value : [])
                : array_replace($default, is_array($value) ? $value : []);
        }

        $this->form->fill($data);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Tabs::make('Settings')
                    ->persistTabInQueryString()
                    ->tabs([
                        Tab::make('Company')
                            ->icon(Heroicon::OutlinedBuildingOffice2)
                            ->columns(2)
                            ->schema([
                                TextInput::make('company.name')->label('Trading name')->required(),
                                TextInput::make('company.legal_name')->label('Legal name'),
                                TextInput::make('company.tagline')->columnSpanFull(),
                                TextInput::make('company.phone')->tel(),
                                TextInput::make('company.whatsapp')->label('WhatsApp')->tel()
                                    ->helperText('International format, e.g. +9779851000000'),
                                TextInput::make('company.email')->email(),
                                TextInput::make('company.hours')->label('Opening hours'),
                                TextInput::make('company.registration')
                                    ->label('Registration details')
                                    ->placeholder('Govt. reg. no. … · NTB licence no. …')
                                    ->helperText('Optional. Shown in the site footer.')
                                    ->maxLength(255)
                                    ->columnSpanFull(),
                                Textarea::make('company.address')->rows(2)->columnSpanFull(),
                            ]),
                        Tab::make('Social')
                            ->icon(Heroicon::OutlinedShare)
                            ->columns(2)
                            ->schema(collect(['facebook' => 'Facebook', 'instagram' => 'Instagram', 'tripadvisor' => 'Tripadvisor', 'tiktok' => 'TikTok', 'youtube' => 'YouTube', 'twitter' => 'X / Twitter'])
                                ->map(fn (string $label, string $key) => TextInput::make("social.{$key}")->label($label)->url())
                                ->values()
                                ->all()),
                        Tab::make('Stats & reviews')
                            ->icon(Heroicon::OutlinedChartBar)
                            ->schema([
                                Section::make('Headline stats')
                                    ->description('Shown in stats blocks across the site.')
                                    ->schema([
                                        Repeater::make('stats')
                                            ->hiddenLabel()
                                            ->columns(2)
                                            ->schema([
                                                TextInput::make('value')->required()->placeholder('2,500+'),
                                                TextInput::make('label')->required()->placeholder('Happy travellers'),
                                            ])
                                            ->defaultItems(0)
                                            ->reorderable()
                                            ->maxItems(8)
                                            ->addActionLabel('Add stat'),
                                    ]),
                                Section::make('Google reviews')
                                    ->columns(3)
                                    ->schema([
                                        TextInput::make('reviews.google_rating')->label('Rating')->numeric()->minValue(0)->maxValue(5)->step(0.1)
                                            ->helperText('Leave blank to show the label instead of a number.'),
                                        TextInput::make('reviews.google_label')->label('Rating label')->placeholder('Excellent')->maxLength(50),
                                        TextInput::make('reviews.google_count')->label('Review count')->numeric()->integer()->minValue(0),
                                        TextInput::make('reviews.google_url')->label('Reviews URL')->url(),
                                        TextInput::make('reviews.tripadvisor_award')->label('Tripadvisor award')->placeholder("Travellers' Choice")->maxLength(100),
                                    ]),
                            ]),
                        Tab::make('Booking')
                            ->icon(Heroicon::OutlinedCreditCard)
                            ->columns(3)
                            ->schema([
                                TextInput::make('booking.deposit_percent')->label('Deposit')->numeric()->integer()->minValue(0)->maxValue(100)->suffix('%')->required(),
                                TextInput::make('booking.balance_due_days')->label('Balance due')->numeric()->integer()->minValue(0)->suffix('days before departure')->required(),
                                Select::make('booking.currency')
                                    ->options(['USD' => 'USD', 'EUR' => 'EUR', 'GBP' => 'GBP', 'AUD' => 'AUD', 'NPR' => 'NPR'])
                                    ->selectablePlaceholder(false)
                                    ->required(),
                            ]),
                        Tab::make('B2B')
                            ->icon(Heroicon::OutlinedBriefcase)
                            ->schema([
                                TagsInput::make('b2b.credentials')
                                    ->label('Credentials')
                                    ->placeholder('e.g. Nepal Tourism Board licensed')
                                    ->helperText('Badges shown on the B2B partners page. Press Enter after each one.')
                                    ->reorderable(),
                            ]),
                        Tab::make('Notifications')
                            ->icon(Heroicon::OutlinedBell)
                            ->schema([
                                TextInput::make('notifications.sales_email')
                                    ->label('Sales inbox')
                                    ->helperText('New enquiries and bookings are emailed here.')
                                    ->email(),
                            ]),
                        Tab::make('Integrations')
                            ->icon(Heroicon::OutlinedPuzzlePiece)
                            ->columns(2)
                            ->schema([
                                TextInput::make('analytics.gtm_id')->label('Google Tag Manager ID')->placeholder('GTM-XXXXXXX'),
                                TextInput::make('analytics.ga4_id')->label('GA4 measurement ID')->placeholder('G-XXXXXXXXXX'),
                                TextInput::make('integrations.mailchimp_list_id')->label('Mailchimp audience ID'),
                                TextInput::make('integrations.turnstile_site_key')->label('Cloudflare Turnstile site key'),
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
                        Action::make('save')
                            ->label('Save settings')
                            ->submit('save')
                            ->keyBindings(['mod+s']),
                    ])->key('form-actions'),
                ]),
        ]);
    }

    public function save(): void
    {
        abort_unless(static::canAccess(), 403);

        $state = $this->form->getState();

        foreach (self::DEFAULTS as $key => $default) {
            // Merge over the stored value so keys not on this form (e.g. added by importers) survive.
            $value = $state[$key] ?? $default;

            $value = $key === 'stats'
                ? array_values($value ?? [])
                : array_replace($default, (array) Setting::get($key, []), self::castNumbers($key, $value ?? []));

            if ($key === 'b2b') {
                $value['credentials'] = array_values(array_filter((array) ($value['credentials'] ?? []), 'filled'));
            }

            Setting::put($key, $value);
        }

        Notification::make()
            ->title('Settings saved')
            ->success()
            ->send();
    }

    /**
     * @param  array<string, mixed>  $value
     * @return array<string, mixed>
     */
    protected static function castNumbers(string $key, array $value): array
    {
        $casts = [
            'reviews' => ['google_rating' => 'float', 'google_count' => 'int'],
            'booking' => ['deposit_percent' => 'int', 'balance_due_days' => 'int'],
        ][$key] ?? [];

        foreach ($casts as $field => $type) {
            if (! array_key_exists($field, $value)) {
                continue;
            }

            $value[$field] = ($value[$field] === null || $value[$field] === '')
                ? null
                : ($type === 'int' ? (int) $value[$field] : (float) $value[$field]);
        }

        return $value;
    }
}
