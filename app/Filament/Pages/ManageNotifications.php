<?php

namespace App\Filament\Pages;

use App\Models\Booking;
use App\Models\Lead;
use App\Models\Payment;
use App\Models\Quote;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\BalancePaymentDue;
use App\Notifications\BookingConfirmed;
use App\Notifications\EnquiryReceived;
use App\Notifications\PaymentReceived;
use App\Notifications\QuoteSent;
use App\Notifications\StaffAlert;
use App\Support\Notifications\NotificationSettings;
use App\Support\Permissions;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
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
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Notifications\Notification as BaseNotification;
use Illuminate\Support\HtmlString;
use UnitEnum;

/**
 * Settings → Notifications: switch customer emails and staff alerts on or off, edit their wording,
 * and choose which team members get each alert (email and/or the admin bell).
 *
 * @property-read Schema $form
 */
class ManageNotifications extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBellAlert;

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Notifications';

    protected static ?int $navigationSort = 15;

    protected static ?string $title = 'Notifications';

    protected static ?string $slug = 'notifications';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->can(Permissions::SETTINGS) ?? false;
    }

    public function mount(): void
    {
        $this->form->fill(collect(NotificationSettings::EVENTS)->mapWithKeys(fn ($event, string $key) => [$key => NotificationSettings::event($key)])->all());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Tabs::make('Notifications')
                    ->persistTabInQueryString()
                    ->tabs([
                        Tab::make('Customer emails')
                            ->icon(Heroicon::OutlinedEnvelope)
                            ->schema(collect(NotificationSettings::forAudience(NotificationSettings::CUSTOMER))->map(fn (array $event, string $key) => $this->customerSection($key, $event))->values()->all()),
                        Tab::make('Staff alerts')
                            ->icon(Heroicon::OutlinedUserGroup)
                            ->schema(collect(NotificationSettings::forAudience(NotificationSettings::STAFF))->map(fn (array $event, string $key) => $this->staffSection($key, $event))->values()->all()),
                    ]),
            ]);
    }

    /** @param  array<string, mixed>  $event */
    private function customerSection(string $key, array $event): Section
    {
        return Section::make($event['label'])
            ->description($event['description'])
            ->icon(Heroicon::OutlinedEnvelope)
            ->collapsible()
            ->collapsed()
            ->headerActions([
                Action::make("preview_{$key}")
                    ->label('Email me a preview')
                    ->icon(Heroicon::OutlinedPaperAirplane)
                    ->link()
                    ->action(fn () => $this->sendPreview($key)),
            ])
            ->schema([
                Toggle::make("{$key}.enabled")
                    ->label('Send this email')
                    ->live()
                    ->visible($event['can_disable']),
                TextInput::make("{$key}.subject")->label('Subject')->required()->maxLength(160)
                    ->disabled(fn (Get $get): bool => $event['can_disable'] && ! $get("{$key}.enabled")),
                Textarea::make("{$key}.intro")->label('Opening message')->rows(3)->maxLength(1500)
                    ->helperText('The booking details, amounts, links and contact lines are added automatically below this message.')
                    ->disabled(fn (Get $get): bool => $event['can_disable'] && ! $get("{$key}.enabled")),
                TextInput::make("{$key}.signature")->label('Sign-off')->maxLength(160)
                    ->disabled(fn (Get $get): bool => $event['can_disable'] && ! $get("{$key}.enabled")),
                $this->placeholderHelp($event['placeholders']),
            ]);
    }

    /** @param  array<string, mixed>  $event */
    private function staffSection(string $key, array $event): Section
    {
        $assigneeOnly = (bool) ($event['assignee_only'] ?? false);

        return Section::make($event['label'])
            ->description($event['description'])
            ->icon(Heroicon::OutlinedBell)
            ->collapsible()
            ->columns(2)
            ->headerActions([
                Action::make("test_{$key}")
                    ->label('Send me a test')
                    ->icon(Heroicon::OutlinedBellAlert)
                    ->link()
                    ->action(fn () => $this->sendStaffTest($key)),
            ])
            ->schema([
                Toggle::make("{$key}.enabled")->label('Send this alert')->live()->columnSpanFull(),
                CheckboxList::make("{$key}.channels")
                    ->label('How')
                    ->options([NotificationSettings::CHANNEL_BELL => 'Admin bell (in-app)', NotificationSettings::CHANNEL_MAIL => 'Email to each person'])
                    ->required(fn (Get $get): bool => (bool) $get("{$key}.enabled"))
                    ->visible(fn (Get $get): bool => (bool) $get("{$key}.enabled")),
                TextInput::make("{$key}.subject")->label('Title / email subject')->required()->maxLength(160)
                    ->visible(fn (Get $get): bool => (bool) $get("{$key}.enabled")),
                Toggle::make("{$key}.all_staff")
                    ->label('Everyone with CRM access')
                    ->helperText('Admins, managers and sales staff. Turn off to pick people.')
                    ->live()
                    ->hidden(fn (Get $get): bool => $assigneeOnly || ! $get("{$key}.enabled")),
                Select::make("{$key}.user_ids")
                    ->label('Team members')
                    ->multiple()
                    ->options(fn (): array => User::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable()
                    ->visible(fn (Get $get): bool => ! $assigneeOnly && $get("{$key}.enabled") && ! $get("{$key}.all_staff")),
                TagsInput::make("{$key}.emails")
                    ->label('Also email these addresses')
                    ->placeholder('sales@wonderhimalaya.com')
                    ->nestedRecursiveRules(['email'])
                    ->helperText('Shared inboxes or people without an admin login. Always sent by email.')
                    ->hidden(fn (Get $get): bool => $assigneeOnly || ! $get("{$key}.enabled"))
                    ->columnSpanFull(),
                $this->placeholderHelp($event['placeholders'])->columnSpanFull()
                    ->visible(fn (Get $get): bool => (bool) $get("{$key}.enabled")),
            ]);
    }

    /** @param  array<int, string>  $placeholders */
    private function placeholderHelp(array $placeholders): Text
    {
        return Text::make(new HtmlString('Placeholders: '.collect($placeholders)->map(fn ($p) => '<code>{'.e($p).'}</code>')->implode(' ')))
            ->color('gray')
            ->size('sm');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')->label('Save notifications')->submit('save')->keyBindings(['mod+s']),
                    ])->key('form-actions'),
                ]),
        ]);
    }

    public function save(bool $quiet = false): void
    {
        abort_unless(static::canAccess(), 403);

        $state = $this->form->getState();
        $settings = [];

        foreach (NotificationSettings::EVENTS as $key => $event) {
            $value = (array) ($state[$key] ?? []);
            $settings[$key] = [
                'enabled' => (bool) ($value['enabled'] ?? true),
                'subject' => trim((string) ($value['subject'] ?? '')) ?: $event['subject'],
                'intro' => trim((string) ($value['intro'] ?? '')),
                'signature' => trim((string) ($value['signature'] ?? '')),
                'channels' => array_values((array) ($value['channels'] ?? [])),
                'all_staff' => (bool) ($value['all_staff'] ?? false),
                'user_ids' => array_map('intval', (array) ($value['user_ids'] ?? [])),
                'emails' => array_values(array_filter((array) ($value['emails'] ?? []), fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL))),
            ];
        }

        Setting::put('notification_settings', $settings);

        if (! $quiet) {
            Notification::make()->title('Notification settings saved')->success()->send();
        }
    }

    /** Saves the form, then emails the current user this customer email built from the latest real record. */
    private function sendPreview(string $key): void
    {
        $this->save(quiet: true);
        $notification = $this->previewNotification($key);

        if (! $notification) {
            Notification::make()->title('Nothing to preview yet')->body('A preview needs at least one real '.match ($key) {
                'quote_sent' => 'quote', 'payment_received' => 'paid payment', 'booking_confirmed', 'balance_due' => 'booking', default => 'enquiry',
            }.' in the CRM.')->warning()->send();

            return;
        }

        try {
            auth()->user()->notifyNow($notification, ['mail']);
            Notification::make()->title('Preview sent to '.auth()->user()->email)
                ->body(config('mail.default') === 'log' ? 'Mail is in "log" mode: check storage/logs/laravel.log.' : null)->success()->send();
        } catch (\Throwable $e) {
            Notification::make()->title('Preview could not be sent')->body(str($e->getMessage())->limit(300)->toString())->danger()->send();
        }
    }

    private function previewNotification(string $key): ?BaseNotification
    {
        return match ($key) {
            'enquiry_received' => ($lead = Lead::with('contact')->latest('id')->first()) ? new EnquiryReceived($lead) : null,
            'quote_sent' => ($quote = Quote::with('contact')->latest('id')->first()) ? new QuoteSent($quote) : null,
            'payment_received' => ($payment = Payment::where('status', 'paid')->latest('id')->first()) ? new PaymentReceived($payment) : null,
            'booking_confirmed' => ($booking = Booking::latest('id')->first()) ? new BookingConfirmed($booking) : null,
            'balance_due' => ($payment = Payment::whereHasMorph('payable', [Booking::class])->latest('id')->first()) ? new BalancePaymentDue($payment->payable, $payment) : null,
            default => null,
        };
    }

    private function sendStaffTest(string $key): void
    {
        $this->save(quiet: true);
        $event = NotificationSettings::EVENTS[$key];
        $channels = NotificationSettings::channels($key);

        if (! NotificationSettings::enabled($key) || $channels === []) {
            Notification::make()->title('This alert is switched off')->warning()->send();

            return;
        }

        try {
            auth()->user()->notifyNow(new StaffAlert($key, '[Test] '.$event['label'], ['This is how the "'.$event['label'].'" alert will look.'], 'Open the admin', url('/admin')), $channels);
            Notification::make()->title('Test sent')->body('Check the bell'.(in_array('mail', $channels, true) ? ' and your inbox ('.auth()->user()->email.')' : '').'.')->success()->send();
        } catch (\Throwable $e) {
            Notification::make()->title('Test could not be sent')->body(str($e->getMessage())->limit(300)->toString())->danger()->send();
        }
    }
}
