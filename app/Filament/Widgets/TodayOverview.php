<?php

namespace App\Filament\Widgets;

use App\Enums\BookingStatus;
use App\Enums\DepartureStatus;
use App\Enums\LeadStatus;
use App\Filament\Pages\Dashboard;
use App\Filament\Resources\Bookings\BookingResource;
use App\Filament\Resources\CrmTasks\CrmTaskResource;
use App\Filament\Resources\CrmTasks\Tables\CrmTasksTable;
use App\Filament\Resources\Leads\LeadResource;
use App\Filament\Resources\TripDepartures\TripDepartureResource;
use App\Filament\Support\RecordLinks;
use App\Models\Booking;
use App\Models\CrmTask;
use App\Models\Lead;
use App\Models\TripDeparture;
use App\Models\User;
use App\Support\Money;
use App\Support\Permissions;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * "Today" panel: what needs doing now — follow-ups and tasks that are due,
 * departures leaving this week and booking balances falling due.
 */
class TodayOverview extends Widget
{
    /** How many rows each list shows before linking to the full list. */
    public const LIMIT = 5;

    public const DEPARTURE_DAYS = 7;

    public const BALANCE_DAYS = 14;

    protected static ?int $sort = 0;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.today-overview';

    public static function canView(): bool
    {
        $user = auth()->user();

        return (bool) collect(['leads', 'tasks', 'bookings', 'departures'])
            ->contains(fn (string $section): bool => (bool) $user?->can(Permissions::for($section, Permissions::VIEW)));
    }

    public function completeTask(int $taskId): void
    {
        $task = CrmTask::query()->findOrFail($taskId);

        abort_unless(auth()->user()?->can('update', $task), 403);

        $task->update(['completed_at' => now()]);

        Notification::make()->title('Task completed')->body($task->title)->success()->send();
    }

    /** @return array<string, mixed> */
    protected function getViewData(): array
    {
        /** @var User $user */
        $user = auth()->user();
        $can = fn (string $section): bool => $user->can(Permissions::for($section, Permissions::VIEW));
        $now = now(Dashboard::TIMEZONE);

        return [
            'now' => $now,
            'seesEveryone' => $user->can(Permissions::LEADS_VIEW_ALL),
            'followUps' => $can('leads') ? $this->followUps($user, $now) : null,
            'tasks' => $can('tasks') ? $this->tasks($user, $now) : null,
            'departures' => $can('departures') || $can('bookings') ? $this->departures($user, $now) : null,
            'balances' => $can('bookings') ? $this->balances($now) : null,
        ];
    }

    /** @return array{count: int, items: list<array{title: string, meta: string, when: string, overdue: bool, url: ?string}>, url: ?string} */
    private function followUps(User $user, Carbon $now): array
    {
        $query = Lead::query()
            ->with(['contact', 'trip'])
            ->whereNotNull('next_follow_up_at')
            ->where('next_follow_up_at', '<=', $now->copy()->endOfDay()->utc())
            ->whereNotIn('status', [LeadStatus::Lost, LeadStatus::Travelled])
            ->when(! $user->can(Permissions::LEADS_VIEW_ALL), fn (Builder $query) => $query->where('owner_id', $user->id));

        return [
            'count' => (clone $query)->count(),
            'items' => $query->orderBy('next_follow_up_at')->limit(self::LIMIT)->get()
                ->map(fn (Lead $lead): array => [
                    'title' => $lead->contact?->name ?: $lead->reference,
                    'meta' => $lead->reference.' · '.($lead->trip?->title ?? $lead->type?->getLabel()),
                    'when' => $this->relative($lead->next_follow_up_at, $now),
                    'overdue' => $lead->next_follow_up_at->lt($now->copy()->startOfDay()->utc()),
                    'url' => LeadResource::getUrl('view', ['record' => $lead]),
                ])->all(),
            'url' => LeadResource::getUrl('index'),
        ];
    }

    /** @return array{count: int, items: list<array{id: int, title: string, meta: ?string, when: string, overdue: bool, url: ?string, canComplete: bool}>, url: ?string} */
    private function tasks(User $user, Carbon $now): array
    {
        $query = CrmTask::query()
            ->open()
            ->with(['taskable', 'assignee'])
            ->whereNotNull('due_at')
            ->where('due_at', '<=', $now->copy()->endOfDay()->utc())
            ->when(! $user->can(Permissions::LEADS_VIEW_ALL), fn (Builder $query) => $query->where('assignee_id', $user->id));

        return [
            'count' => (clone $query)->count(),
            'items' => $query->orderBy('due_at')->limit(self::LIMIT)->get()
                ->map(fn (CrmTask $task): array => [
                    'id' => $task->id,
                    'title' => $task->title,
                    'meta' => collect([
                        CrmTasksTable::taskableLabel($task),
                        $task->assignee_id !== $user->id ? $task->assignee?->name : null,
                    ])->filter()->implode(' · ') ?: null,
                    'when' => $this->relative($task->due_at, $now),
                    'overdue' => $task->isOverdue(),
                    'url' => CrmTasksTable::taskableUrl($task) ?? RecordLinks::to(CrmTaskResource::class, $task),
                    'canComplete' => $user->can('update', $task),
                ])->all(),
            'url' => CrmTaskResource::getUrl('index'),
        ];
    }

    /** @return array{count: int, items: list<array{title: string, meta: string, when: string, overdue: bool, url: ?string}>, url: ?string} */
    private function departures(User $user, Carbon $now): array
    {
        $query = TripDeparture::query()
            ->with(['trip', 'tier'])
            ->whereDate('start_date', '>=', $now->toDateString())
            ->whereDate('start_date', '<=', $now->copy()->addDays(self::DEPARTURE_DAYS)->toDateString())
            ->whereNot('status', DepartureStatus::Cancelled);

        return [
            'count' => (clone $query)->count(),
            'items' => $query->orderBy('start_date')->limit(self::LIMIT)->get()
                ->map(fn (TripDeparture $departure): array => [
                    'title' => $departure->trip?->title ?? 'Departure',
                    'meta' => collect([$departure->tier?->name, $departure->seats_booked.' of '.$departure->seats_total.' seats booked'])->filter()->implode(' · '),
                    'when' => $departure->start_date->toDateString() === $now->toDateString() ? 'Today' : $departure->start_date->format('D j M'),
                    'overdue' => false,
                    'url' => RecordLinks::to(TripDepartureResource::class, $departure),
                ])->all(),
            'url' => TripDepartureResource::canViewAny() ? TripDepartureResource::getUrl('index') : null,
        ];
    }

    /** @return array{count: int, items: list<array{title: string, meta: string, when: string, overdue: bool, url: ?string}>, url: ?string} */
    private function balances(Carbon $now): array
    {
        $query = Booking::query()
            ->with(['contact', 'trip'])
            ->whereIn('status', [BookingStatus::PendingDeposit, BookingStatus::Confirmed])
            ->whereColumn('amount_paid', '<', 'total')
            ->whereNotNull('balance_due_on')
            ->whereDate('balance_due_on', '<=', $now->copy()->addDays(self::BALANCE_DAYS)->toDateString());

        return [
            'count' => (clone $query)->count(),
            'items' => $query->orderBy('balance_due_on')->limit(self::LIMIT)->get()
                ->map(function (Booking $booking) use ($now): array {
                    $dueOn = $booking->balance_due_on->toDateString();
                    $isOverdue = $dueOn < $now->toDateString();

                    return [
                        'title' => Money::format($booking->balanceDue(), $booking->currency).' · '.($booking->contact?->name ?: $booking->number),
                        'meta' => $booking->number.($booking->trip ? ' · '.$booking->trip->title : ''),
                        'when' => match (true) {
                            $isOverdue => 'Overdue since '.$booking->balance_due_on->format('j M'),
                            $dueOn === $now->toDateString() => 'Due today',
                            default => 'Due '.$booking->balance_due_on->format('D j M'),
                        },
                        'overdue' => $isOverdue,
                        'url' => RecordLinks::to(BookingResource::class, $booking),
                    ];
                })->all(),
            'url' => BookingResource::getUrl('index'),
        ];
    }

    private function relative(Carbon $moment, Carbon $now): string
    {
        $local = $moment->copy()->setTimezone(Dashboard::TIMEZONE);

        return match (true) {
            $local->toDateString() === $now->toDateString() => 'Today '.$local->format('H:i'),
            $local->toDateString() === $now->copy()->subDay()->toDateString() => 'Yesterday',
            default => $local->diffForHumans($now, ['syntax' => Carbon::DIFF_RELATIVE_TO_NOW]),
        };
    }
}
