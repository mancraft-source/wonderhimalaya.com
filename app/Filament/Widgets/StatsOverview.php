<?php

namespace App\Filament\Widgets;

use App\Enums\LeadStatus;
use App\Enums\PaymentStatus;
use App\Filament\Resources\Leads\LeadResource;
use App\Models\Booking;
use App\Models\Lead;
use App\Models\Payment;
use App\Support\Permissions;
use App\Support\QuoteDefaults;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

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

        $stats = [
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

        if (auth()->user()?->can(Permissions::PAYMENTS)) {
            $revenue = (float) Payment::query()
                ->where('status', PaymentStatus::Paid)
                ->whereBetween('paid_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->sum('amount');

            $stats[] = Stat::make('Received this month', $currency.' '.number_format($revenue, 0))
                ->description('Paid payments, all gateways')
                ->descriptionIcon(Heroicon::Banknotes)
                ->color('success');
        }

        return $stats;
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
