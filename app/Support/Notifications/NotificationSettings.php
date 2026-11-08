<?php

namespace App\Support\Notifications;

use App\Models\Setting;
use App\Models\User;
use App\Support\Permissions;
use Illuminate\Support\Collection;

/**
 * Settings → Notifications: which emails and staff alerts are sent, their wording, and who receives staff alerts.
 * Stored as the `notification_settings` settings row; every value has a default matching the original copy.
 */
final class NotificationSettings
{
    public const CUSTOMER = 'customer';

    public const STAFF = 'staff';

    public const CHANNEL_MAIL = 'mail';

    public const CHANNEL_BELL = 'database';

    /**
     * @var array<string, array{label: string, audience: string, description: string, subject: string, intro: string, signature?: string, placeholders: array<int, string>, can_disable: bool, assignee_only?: bool}>
     */
    public const EVENTS = [
        'enquiry_received' => [
            'label' => 'Enquiry confirmation',
            'audience' => self::CUSTOMER,
            'description' => 'Sent to the traveller as soon as they submit any enquiry form (trip, tailor-made, contact, B2B).',
            'subject' => 'We have your enquiry — {reference}',
            'intro' => 'Thank you for reaching out to Wonder Himalaya. A trip specialist will reply personally within 24 hours (usually much sooner).',
            'signature' => 'Wander always, wonder often — the Wonder Himalaya team',
            'placeholders' => ['first_name', 'name', 'reference', 'trip', 'phone'],
            'can_disable' => true,
        ],
        'quote_sent' => [
            'label' => 'Quote / proposal',
            'audience' => self::CUSTOMER,
            'description' => 'Sent when a quote is sent from the CRM. The personal message typed when sending replaces the intro.',
            'subject' => 'Your Wonder Himalaya proposal — {quote_title}',
            'intro' => 'Thank you for planning your journey with us. Your personalised proposal is ready.',
            'signature' => 'Wonder Himalaya',
            'placeholders' => ['first_name', 'name', 'quote_title', 'total', 'valid_until'],
            'can_disable' => false,
        ],
        'payment_received' => [
            'label' => 'Payment receipt',
            'audience' => self::CUSTOMER,
            'description' => 'Sent to the payer after every successful online payment or recorded bank transfer.',
            'subject' => 'Payment received — {amount}',
            'intro' => 'Thank you — we have received your payment.',
            'signature' => 'With gratitude — the Wonder Himalaya team',
            'placeholders' => ['first_name', 'name', 'amount', 'booking_number', 'trip'],
            'can_disable' => true,
        ],
        'booking_confirmed' => [
            'label' => 'Booking confirmation',
            'audience' => self::CUSTOMER,
            'description' => 'Sent when the deposit is paid and the booking becomes confirmed.',
            'subject' => 'Your booking is confirmed — {booking_number}',
            'intro' => 'Wonderful news: your booking is confirmed. We cannot wait to welcome you to the Himalaya.',
            'signature' => 'See you in the mountains — the Wonder Himalaya team',
            'placeholders' => ['first_name', 'name', 'booking_number', 'trip', 'start_date'],
            'can_disable' => true,
        ],
        'balance_due' => [
            'label' => 'Balance payment reminder',
            'audience' => self::CUSTOMER,
            'description' => 'Sent by the daily scheduler before the balance due date, with a secure payment link.',
            'subject' => 'Balance due for your trip — {booking_number}',
            'intro' => 'Your adventure is getting close! The remaining balance for {trip} is due{due_by}.',
            'signature' => 'The Wonder Himalaya team',
            'placeholders' => ['first_name', 'name', 'booking_number', 'trip', 'amount', 'due_by'],
            'can_disable' => true,
        ],
        'new_lead' => [
            'label' => 'New enquiry',
            'audience' => self::STAFF,
            'description' => 'A new lead arrived from the website, the tailor-made builder or the API.',
            'subject' => 'New {type} lead {reference} — {name}',
            'intro' => '',
            'placeholders' => ['type', 'reference', 'name', 'email', 'trip', 'country'],
            'can_disable' => true,
        ],
        'payment_received_staff' => [
            'label' => 'Payment received',
            'audience' => self::STAFF,
            'description' => 'A customer paid online (PayPal / card) or a bank transfer was marked as received.',
            'subject' => 'Payment received: {amount} — {name}',
            'intro' => '',
            'placeholders' => ['amount', 'name', 'booking_number', 'trip'],
            'can_disable' => true,
        ],
        'booking_confirmed_staff' => [
            'label' => 'Booking confirmed',
            'audience' => self::STAFF,
            'description' => 'A booking became confirmed after its deposit was paid.',
            'subject' => 'Booking confirmed: {booking_number} — {trip}',
            'intro' => '',
            'placeholders' => ['booking_number', 'trip', 'name', 'start_date'],
            'can_disable' => true,
        ],
        'lead_assigned' => [
            'label' => 'Lead assigned to me',
            'audience' => self::STAFF,
            'description' => 'Tells a team member when a lead is assigned to them in the CRM.',
            'subject' => 'Lead {reference} assigned to you — {name}',
            'intro' => '',
            'placeholders' => ['reference', 'name', 'trip'],
            'can_disable' => true,
            'assignee_only' => true,
        ],
    ];

    /** @return array{enabled: bool, subject: string, intro: string, signature: string, emails: array<int, string>, all_staff: bool, user_ids: array<int, int>, channels: array<int, string>} */
    public static function event(string $key): array
    {
        $definition = self::EVENTS[$key];
        $defaults = [
            'enabled' => true,
            'subject' => $definition['subject'],
            'intro' => $definition['intro'],
            'signature' => $definition['signature'] ?? '',
            // The original sales inbox keeps receiving new-lead emails until someone changes the recipients.
            'emails' => $key === 'new_lead' && filled($sales = Setting::get('notifications.sales_email')) ? [$sales] : [],
            'all_staff' => $key !== 'new_lead' || blank(Setting::get('notifications.sales_email')),
            'user_ids' => [],
            'channels' => $definition['audience'] === self::STAFF ? [self::CHANNEL_BELL] : [self::CHANNEL_MAIL],
        ];

        $stored = array_filter((array) Setting::get("notification_settings.{$key}", []), fn ($value) => $value !== null && $value !== '');

        return array_replace($defaults, $stored);
    }

    public static function enabled(string $key): bool
    {
        return ! self::EVENTS[$key]['can_disable'] || (bool) self::event($key)['enabled'];
    }

    /** @param  array<string, string|int|float|null>  $values */
    public static function render(string $key, string $field, array $values): string
    {
        $text = (string) (self::event($key)[$field] ?: (self::EVENTS[$key][$field] ?? ''));

        return trim(preg_replace_callback('/\{([a-z_]+)\}/', fn (array $m): string => (string) ($values[$m[1]] ?? ''), $text));
    }

    /**
     * Staff who receive this alert in the admin (bell and/or email to their login address).
     *
     * @return Collection<int, User>
     */
    public static function staff(string $key): Collection
    {
        $settings = self::event($key);

        if ($settings['all_staff']) {
            return User::query()->where('is_active', true)->get()->filter(fn (User $user) => $user->can(Permissions::CRM_VIEW))->values();
        }

        return User::query()->where('is_active', true)->whereKey($settings['user_ids'])->get();
    }

    /** @return array<int, string> */
    public static function channels(string $key): array
    {
        return array_values(array_intersect((array) self::event($key)['channels'], [self::CHANNEL_MAIL, self::CHANNEL_BELL]));
    }

    /** @return array<string, array<string, mixed>> */
    public static function forAudience(string $audience): array
    {
        return array_filter(self::EVENTS, fn (array $event) => $event['audience'] === $audience);
    }
}
