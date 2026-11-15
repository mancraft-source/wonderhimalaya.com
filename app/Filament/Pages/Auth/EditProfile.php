<?php

namespace App\Filament\Pages\Auth;

use App\Models\User;
use App\Support\Notifications\NotificationSettings;
use App\Support\UserAgent;
use Filament\Actions\Action;
use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Facades\Filament;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * The admin profile page: Filament's name / email / password form and two-factor setup, plus
 * personal staff-alert preferences and session security (recent sign-ins, active sessions).
 *
 * @property-read Schema $form
 * @property-read Schema $notificationsForm
 */
class EditProfile extends BaseEditProfile
{
    /** @var array<string, mixed>|null */
    public ?array $notificationPreferences = [];

    public function mount(): void
    {
        parent::mount();

        $this->notificationsForm->fill(collect($this->eligibleStaffAlerts())
            ->mapWithKeys(fn (array $event, string $key): array => [$key => NotificationSettings::channelsFor($key, $this->user())])
            ->all());
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getFormContentComponent(),
                ...Arr::wrap($this->getMultiFactorAuthenticationContentComponent()),
                $this->getNotificationPreferencesContentComponent(),
                $this->getSessionSecurityContentComponent(),
            ]);
    }

    // ---------------------------------------------------------------- Notifications

    public function notificationsForm(Schema $schema): Schema
    {
        $events = $this->eligibleStaffAlerts();

        if ($events === []) {
            return $schema->components([
                Text::make('Settings → Notifications does not send you any staff alerts at the moment, so there is nothing to choose here.')->color('gray'),
            ]);
        }

        return $schema
            ->statePath('notificationPreferences')
            ->inlineLabel(! static::isSimple())
            ->components(collect($events)->map(fn (array $event, string $key): CheckboxList => CheckboxList::make($key)
                ->label($event['label'])
                ->helperText($event['description'].' Untick everything to stop this alert.')
                ->options(Arr::only([
                    NotificationSettings::CHANNEL_BELL => 'Admin bell (in-app)',
                    NotificationSettings::CHANNEL_MAIL => 'Email to '.$this->user()->email,
                ], NotificationSettings::channels($key)))
                ->bulkToggleable(false))
                ->values()
                ->all());
    }

    public function getNotificationPreferencesContentComponent(): Component
    {
        return Section::make('Notifications')
            ->description('Choose how you hear about each staff alert. Your choices can only switch alerts off for you: who receives what, and on which channels, is set under Settings → Notifications.')
            ->icon(Heroicon::OutlinedBell)
            ->schema([
                Form::make([EmbeddedSchema::make('notificationsForm')])
                    ->id('notifications-form')
                    ->livewireSubmitHandler('saveNotificationPreferences')
                    ->footer([
                        Actions::make([
                            Action::make('saveNotificationPreferences')
                                ->label('Save notification preferences')
                                ->submit('saveNotificationPreferences')
                                ->visible(fn (): bool => $this->eligibleStaffAlerts() !== []),
                        ])->key('notifications-form-actions'),
                    ]),
            ]);
    }

    public function saveNotificationPreferences(): void
    {
        $events = $this->eligibleStaffAlerts();
        $state = $events === [] ? [] : $this->notificationsForm->getState();
        $user = $this->user();
        $preferences = (array) ($user->notification_preferences ?? []);

        // Only the alerts and channels the admin settings currently allow are touched; anything else is kept as it was.
        foreach ($events as $key => $event) {
            foreach (NotificationSettings::channels($key) as $channel) {
                $preferences[$key][$channel] = in_array($channel, (array) ($state[$key] ?? []), true);
            }
        }

        $user->forceFill(['notification_preferences' => $preferences])->save();

        Notification::make()->title('Notification preferences saved')->success()->send();
    }

    /**
     * Staff alerts the admin settings would send this person.
     *
     * @return array<string, array<string, mixed>>
     */
    public function eligibleStaffAlerts(): array
    {
        return array_filter(
            NotificationSettings::forAudience(NotificationSettings::STAFF),
            fn (array $event, string $key): bool => NotificationSettings::isEligible($key, $this->user()),
            ARRAY_FILTER_USE_BOTH,
        );
    }

    // ---------------------------------------------------------------- Sessions

    public function getSessionSecurityContentComponent(): Component
    {
        return View::make('filament.pages.profile.session-security');
    }

    /**
     * The latest and previous sign-ins recorded on the user (see RecordLogin).
     *
     * @return array<int, array{label: string, at: ?Carbon, ip: ?string}>
     */
    public function recentLogins(): array
    {
        $user = $this->user();

        return [
            ['label' => 'Last sign-in', 'at' => $user->last_login_at, 'ip' => $user->last_login_ip],
            ['label' => 'Sign-in before that', 'at' => $user->previous_login_at, 'ip' => $user->previous_login_ip],
        ];
    }

    /**
     * This user's sessions in the database session store, newest first. Session ids never leave the server:
     * each row is addressed by a hash of its id.
     *
     * @return Collection<int, array{key: string, device: string, ip: ?string, last_active: Carbon, is_current: bool}>
     */
    public function activeSessions(): Collection
    {
        $currentId = $this->currentSessionId();

        return $this->sessionsQuery()
            ->orderByDesc('last_activity')
            ->get(['id', 'ip_address', 'user_agent', 'last_activity'])
            ->map(fn (object $session): array => [
                'key' => self::sessionKey($session->id),
                'device' => UserAgent::summary($session->user_agent),
                'ip' => $session->ip_address,
                'last_active' => Carbon::createFromTimestamp($session->last_activity),
                'is_current' => $session->id === $currentId,
            ]);
    }

    public function usesDatabaseSessions(): bool
    {
        return config('session.driver') === 'database';
    }

    public function signOutOtherDevicesAction(): Action
    {
        return Action::make('signOutOtherDevices')
            ->label('Sign out other devices')
            ->icon(Heroicon::OutlinedArrowRightStartOnRectangle)
            ->color('danger')
            ->modalHeading('Sign out of all other devices?')
            ->modalDescription('Every other browser and device signed in to your account is signed out, including ones that ticked "Remember me". This one stays signed in.')
            ->modalSubmitActionLabel('Sign out other devices')
            ->schema([
                TextInput::make('password')
                    ->label('Current password')
                    ->password()
                    ->revealable(filament()->arePasswordsRevealable())
                    ->autocomplete('current-password')
                    ->currentPassword(guard: Filament::getAuthGuard())
                    ->required(),
            ])
            ->action(function (array $data): void {
                // Re-hashes the password, so other sessions (and remember-me cookies) fail AuthenticateSession.
                Filament::auth()->logoutOtherDevices($data['password']);

                session()->put(['password_hash_'.Filament::getAuthGuard() => $this->user()->fresh()->getAuthPassword()]);

                $removed = $this->otherSessionsQuery()->delete();

                Notification::make()
                    ->title('Signed out of other devices')
                    ->body($removed ? "{$removed} other ".str('session')->plural($removed).' ended.' : null)
                    ->success()
                    ->send();
            });
    }

    public function signOutSessionAction(): Action
    {
        return Action::make('signOutSession')
            ->label('Sign out')
            ->icon(Heroicon::OutlinedXMark)
            ->color('gray')
            ->link()
            ->size('sm')
            ->requiresConfirmation()
            ->modalHeading('Sign out this session?')
            ->modalDescription('The browser is signed out the next time it loads a page. If it ticked "Remember me", use "Sign out other devices" instead to be sure.')
            ->action(function (array $arguments): void {
                $sessionId = $this->otherSessionsQuery()->pluck('id')
                    ->first(fn (string $id): bool => hash_equals(self::sessionKey($id), (string) ($arguments['session'] ?? '')));

                if ($sessionId === null) {
                    Notification::make()->title('That session has already ended')->warning()->send();

                    return;
                }

                $this->sessionsQuery()->where('id', $sessionId)->delete();

                Notification::make()->title('Session signed out')->success()->send();
            });
    }

    public static function sessionKey(string $sessionId): string
    {
        return hash_hmac('sha256', $sessionId, (string) config('app.key'));
    }

    private function sessionsQuery(): Builder
    {
        return DB::connection(config('session.connection'))
            ->table(config('session.table', 'sessions'))
            ->where('user_id', $this->user()->getKey());
    }

    /** Every session of this user except the one making the request. */
    private function otherSessionsQuery(): Builder
    {
        return $this->sessionsQuery()->where('id', '!=', $this->currentSessionId());
    }

    private function currentSessionId(): string
    {
        return session()->getId();
    }

    private function user(): User
    {
        /** @var User */
        return $this->getUser();
    }
}
