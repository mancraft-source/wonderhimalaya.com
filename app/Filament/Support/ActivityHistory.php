<?php

namespace App\Filament\Support;

use App\Models\Booking;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\Payment;
use App\Models\Quote;
use App\Models\Trip;
use App\Models\User;
use App\Support\Permissions;
use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
use Throwable;

/**
 * Reads the spatie activity log for the admin: who changed what, with an old → new diff
 * per attribute. Sensitive attributes (encrypted casts, hidden attributes, anything that
 * looks like a secret, token, passport or gateway payload) are always masked.
 */
final class ActivityHistory
{
    public const MASK = '•••••• (hidden)';

    /** Entries shown in a record's History slide-over. */
    public const TIMELINE_LIMIT = 100;

    /** Logged models (morph alias => label), for filters. */
    public const SUBJECT_TYPES = [
        'lead' => 'Lead',
        'contact' => 'Contact',
        'company' => 'Company',
        'quote' => 'Quote',
        'booking' => 'Booking',
        'payment' => 'Payment',
        'trip' => 'Trip',
        'role' => 'Role',
        'user' => 'User',
    ];

    public const EVENTS = [
        'created' => 'Created',
        'updated' => 'Updated',
        'deleted' => 'Deleted',
        'restored' => 'Restored',
    ];

    private const SENSITIVE_PATTERN = '/passport|secret|token|password|payload|api_?key|cvv|card_number|iban|signature|private_key|emergency_contact|medical|insurance|date_of_birth/i';

    /** @var array<string, Model|null> */
    private static array $modelPrototypes = [];

    /** Slide-over timeline of a record's own activity, for Edit/View page headers. */
    public static function action(): Action
    {
        return Action::make('history')
            ->label('History')
            ->icon(Heroicon::OutlinedClock)
            ->color('gray')
            ->slideOver()
            ->modalWidth('2xl')
            ->modalHeading('History')
            ->modalDescription('Changes to this record, newest first.')
            ->modalContent(fn (Model $record) => view('filament.activity.timeline', [
                'entries' => self::timeline($record),
            ]))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Close');
    }

    /**
     * The record's own activity, newest first.
     *
     * @return array<int, array{id: int, when: ?CarbonImmutable, causer: string, event: string, event_label: string, event_color: string, changes: array<int, array{attribute: string, label: string, old: string, new: string}>}>
     */
    public static function timeline(Model $record): array
    {
        $activities = Activity::query()
            ->forSubject($record)
            ->with('causer')
            ->latest()
            ->latest('id')
            ->limit(self::TIMELINE_LIMIT)
            ->get();

        $userNames = self::userNamesFor($activities->all());

        return $activities
            ->map(fn (Activity $activity): array => [
                'id' => $activity->id,
                'when' => $activity->created_at?->toImmutable(),
                'causer' => self::causerName($activity),
                'event' => (string) $activity->event,
                'event_label' => self::eventLabel($activity),
                'event_color' => self::eventColor($activity->event),
                'changes' => self::changes($activity, $userNames),
            ])
            ->all();
    }

    /**
     * One row per changed attribute, with sensitive values masked.
     *
     * @param  array<int, string>|null  $userNames  id => name for owner/user columns (looked up when null)
     * @return array<int, array{attribute: string, label: string, old: string, new: string}>
     */
    public static function changes(Activity $activity, ?array $userNames = null): array
    {
        $changes = $activity->attribute_changes?->toArray() ?? [];
        $new = (array) ($changes['attributes'] ?? []);
        $old = (array) ($changes['old'] ?? []);
        $userNames ??= self::userNamesFor([$activity]);
        $model = self::prototype($activity->subject_type);

        return collect(array_keys($new + $old))
            ->map(fn (string $attribute): array => [
                'attribute' => $attribute,
                'label' => self::attributeLabel($attribute),
                'old' => self::formatValue($model, $attribute, $old[$attribute] ?? null, array_key_exists($attribute, $old), $userNames),
                'new' => self::formatValue($model, $attribute, $new[$attribute] ?? null, array_key_exists($attribute, $new), $userNames),
            ])
            ->values()
            ->all();
    }

    public static function isSensitive(string $attribute, ?Model $model = null): bool
    {
        if (preg_match(self::SENSITIVE_PATTERN, $attribute)) {
            return true;
        }

        if (! $model) {
            return false;
        }

        $cast = $model->getCasts()[$attribute] ?? null;

        return in_array($attribute, $model->getHidden(), true)
            || (is_string($cast) && str_starts_with($cast, 'encrypted'));
    }

    public static function causerName(Activity $activity): string
    {
        $causer = $activity->causer;

        if ($causer instanceof User) {
            return $causer->name;
        }

        return $activity->causer_id ? 'Deleted user' : 'System';
    }

    public static function subjectTypeLabel(?string $type): string
    {
        return $type ? (self::SUBJECT_TYPES[$type] ?? Str::headline($type)) : '—';
    }

    /** e.g. "Lead LD-2026-0012", or "Booking #4 (deleted)" when the record is gone. */
    public static function subjectLabel(Activity $activity): string
    {
        $subject = $activity->subject;
        $type = self::subjectTypeLabel($activity->subject_type);

        if (! $subject) {
            return trim($type.' #'.$activity->subject_id.' (deleted)');
        }

        $name = match (true) {
            $subject instanceof Lead => $subject->reference,
            $subject instanceof Booking, $subject instanceof Quote => $subject->number,
            $subject instanceof Contact => $subject->name ?: $subject->email,
            $subject instanceof Company => $subject->name,
            $subject instanceof Trip => $subject->title,
            $subject instanceof Payment => GlobalSearch::money($subject->amount, $subject->currency),
            $subject instanceof Role, $subject instanceof User => $subject->name,
            default => '#'.$subject->getKey(),
        };

        return $type.' '.$name;
    }

    /** Admin URL for the activity's subject when it still exists and the current user may open it. */
    public static function subjectUrl(Activity $activity): ?string
    {
        $subject = $activity->subject;

        if (! $subject) {
            return null;
        }

        if ($subject instanceof Payment) {
            $subject = $subject->payable;

            if (! $subject) {
                return null;
            }
        }

        $resource = Filament::getModelResource($subject);

        if (! $resource) {
            return null;
        }

        try {
            if ($resource::hasPage('view') && $resource::canView($subject)) {
                return $resource::getUrl('view', ['record' => $subject]);
            }

            if ($resource::hasPage('edit') && $resource::canEdit($subject)) {
                return $resource::getUrl('edit', ['record' => $subject]);
            }
        } catch (Throwable) {
            return null;
        }

        return null;
    }

    public static function eventLabel(Activity $activity): string
    {
        return self::EVENTS[$activity->event] ?? Str::headline($activity->event ?: $activity->description);
    }

    public static function eventColor(?string $event): string
    {
        return match ($event) {
            'created' => 'success',
            'deleted' => 'danger',
            'restored' => 'info',
            default => 'warning',
        };
    }

    /** Short summary for tables, e.g. "Status, Owner". */
    public static function summary(Activity $activity): string
    {
        $changes = $activity->attribute_changes?->toArray() ?? [];
        $attributes = array_keys((array) ($changes['attributes'] ?? []) + (array) ($changes['old'] ?? []));

        return implode(', ', array_map(self::attributeLabel(...), $attributes));
    }

    private static function attributeLabel(string $attribute): string
    {
        return match ($attribute) {
            'owner_id' => 'Owner',
            'trip_code' => 'Trip code',
            'gateway_reference' => 'Gateway reference',
            default => Str::of($attribute)->replaceLast('_id', '')->headline()->toString(),
        };
    }

    /**
     * @param  array<int, string>  $userNames
     */
    private static function formatValue(?Model $model, string $attribute, mixed $value, bool $present, array $userNames): string
    {
        if (! $present) {
            return '—';
        }

        if (self::isSensitive($attribute, $model)) {
            return $value === null || $value === '' ? '—' : self::MASK;
        }

        if ($value === null || $value === '') {
            return '—';
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if (in_array($attribute, ['owner_id', 'user_id', 'assignee_id', 'created_by_id'], true) && is_numeric($value)) {
            return $userNames[(int) $value] ?? 'User #'.$value;
        }

        if ($attribute === 'permissions' && is_array($value)) {
            return Permissions::summarize($value);
        }

        if ($attribute === 'roles' && is_array($value)) {
            return $value === [] ? 'No roles' : implode(', ', $value);
        }

        if (is_array($value)) {
            return Str::limit((string) json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), 140);
        }

        $enumLabel = self::enumLabel($model, $attribute, $value);

        if ($enumLabel !== null) {
            return $enumLabel;
        }

        if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $value)) {
            $date = CarbonImmutable::parse($value)->setTimezone(config('app.timezone'));

            return $date->format('H:i:s') === '00:00:00' ? $date->format('j M Y') : $date->format('j M Y, H:i');
        }

        return Str::limit((string) $value, 140);
    }

    private static function enumLabel(?Model $model, string $attribute, mixed $value): ?string
    {
        $cast = $model?->getCasts()[$attribute] ?? null;

        if (! is_string($cast) || ! enum_exists($cast) || ! is_subclass_of($cast, BackedEnum::class)) {
            return null;
        }

        $case = $cast::tryFrom($value);

        return $case instanceof HasLabel ? (string) $case->getLabel() : null;
    }

    private static function prototype(?string $type): ?Model
    {
        if (! $type) {
            return null;
        }

        if (! array_key_exists($type, self::$modelPrototypes)) {
            $class = Relation::getMorphedModel($type) ?? (class_exists($type) ? $type : null);
            self::$modelPrototypes[$type] = $class && is_subclass_of($class, Model::class) ? new $class : null;
        }

        return self::$modelPrototypes[$type];
    }

    /**
     * Names of users referenced by owner-style attributes in these activities.
     *
     * @param  array<int, Activity>  $activities
     * @return array<int, string>
     */
    private static function userNamesFor(array $activities): array
    {
        $ids = [];

        foreach ($activities as $activity) {
            $changes = $activity->attribute_changes?->toArray() ?? [];

            foreach (['attributes', 'old'] as $side) {
                foreach (['owner_id', 'user_id', 'assignee_id', 'created_by_id'] as $attribute) {
                    $id = $changes[$side][$attribute] ?? null;

                    if (is_numeric($id)) {
                        $ids[] = (int) $id;
                    }
                }
            }
        }

        return $ids === [] ? [] : User::query()->whereKey(array_unique($ids))->pluck('name', 'id')->all();
    }
}
