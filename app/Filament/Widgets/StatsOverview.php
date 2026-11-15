<?php

namespace App\Filament\Widgets;

use App\Enums\LeadStatus;
use App\Filament\Resources\Leads\LeadResource;
use App\Models\Booking;
use App\Models\Lead;
use App\Support\Permissions;
use App\Support\QuoteDefaults;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $heading = 'Sales at a glance';

    /** Lead statuses that still represent an open opportunity. */
    public const OPEN = [LeadStatus::New, LeadStatus::Contacted, LeadStatus::Qualified, LeadStatus::Quoted];

    public static function canView(): bool
    {
        return (bool) auth()->user()?->can(Permissions::CRM_VIEW);
    }

    protected function getStats(): array
    {
        $leads = fn () => LeadResource::scopeVisibleTo(Lead::query(), auth()->user());
        $currency = QuoteDefaults::currency();

        $thisWeek = $leads()->where('created_at', '>=', now()->startOfWeek())->count();
        $lastWeek = $leads()->whereBetween('created_at', [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()])->count();
        $delta = $thisWeek - $lastWeek;

        $pipeline = (float) $leads()->whereIn('status', self::OPEN)->sum('estimated_value');
        $openCount = $leads()->whereIn('status', self::OPEN)->count();

        $confirmed = Booking::query()->whereBetween('confirmed_at', [now()->startOfMonth(), now()->endOfMonth()])->count();

        return [
            Stat::make('New leads this week', (string) $thisWeek)
                ->description(($delta >= 0 ? '+' : '').$delta.' vs last week ('.$lastWeek.')')
                ->descriptionIcon($delta >= 0 ? Heroicon::ArrowTrendingUp : Heroicon::ArrowTrendingDown)
                ->color($delta >= 0 ? 'success' : 'danger')
                ->chart($this->dailyLeadCounts()),
            Stat::make('Open pipeline', $currency.' '.number_format($pipeline))
                ->description($openCount.' open '.str('lead')->plural($openCount))
                ->descriptionIcon(Heroicon::Funnel)
                ->color('primary'),
            Stat::make('Bookings confirmed this month', (string) $confirmed)
                ->descriptionIcon(Heroicon::Ticket)
                ->description(now()->format('F Y'))
                ->color('success'),
        ];

    }

    /** @return array<int, int> lead counts for the last 7 days (grouped in PHP for DB portability) */
    private function dailyLeadCounts(): array
    {
        $dates = LeadResource::scopeVisibleTo(Lead::query(), auth()->user())
            ->where('created_at', '>=', now()->subDays(6)->startOfDay())
            ->pluck('created_at')
            ->countBy(fn ($date) => $date->toDateString());

        return collect(range(6, 0))
            ->map(fn (int $daysAgo) => (int) ($dates[now()->subDays($daysAgo)->toDateString()] ?? 0))
            ->all();
    }
}
