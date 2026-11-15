<?php

namespace App\Filament\Widgets;

use App\Enums\PaymentStatus;
use App\Filament\Pages\Dashboard;
use App\Models\Payment;
use App\Support\Money;
use App\Support\Permissions;
use App\Support\QuoteDefaults;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Money received (paid payments only) this month against last month,
 * one stat per currency so amounts in different currencies are never added together.
 */
class RevenueOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    protected ?string $heading = 'Revenue';

    public static function canView(): bool
    {
        return (bool) auth()->user()?->can(Permissions::for('payments', Permissions::VIEW));
    }

    protected function getDescription(): ?string
    {
        return 'Payments received in '.now(Dashboard::TIMEZONE)->format('F').', compared with '.now(Dashboard::TIMEZONE)->subMonthNoOverflow()->format('F');
    }

    protected function getStats(): array
    {
        $thisMonthStart = now(Dashboard::TIMEZONE)->startOfMonth();
        $lastMonthStart = $thisMonthStart->copy()->subMonthNoOverflow();

        $thisMonth = $this->receivedBetween($thisMonthStart, $thisMonthStart->copy()->endOfMonth());
        $lastMonth = $this->receivedBetween($lastMonthStart, $thisMonthStart->copy()->subSecond());

        $currencies = $thisMonth->keys()->merge($lastMonth->keys())->unique()
            ->sortByDesc(fn (string $currency): float => $thisMonth[$currency] ?? 0);

        if ($currencies->isEmpty()) {
            return [
                Stat::make('Received this month', Money::format(0, QuoteDefaults::currency()))
                    ->description('No payments received this month or last')
                    ->descriptionIcon(Heroicon::Banknotes)
                    ->color('gray'),
            ];
        }

        return $currencies->map(function (string $currency) use ($thisMonth, $lastMonth): Stat {
            $current = (float) ($thisMonth[$currency] ?? 0);
            $previous = (float) ($lastMonth[$currency] ?? 0);

            return Stat::make('Received this month · '.$currency, Money::format($current, $currency))
                ->description($this->comparison($current, $previous, $currency))
                ->descriptionIcon($current >= $previous ? Heroicon::ArrowTrendingUp : Heroicon::ArrowTrendingDown)
                ->color($current >= $previous ? 'success' : 'danger');
        })->values()->all();
    }

    /** @return Collection<string, float> currency => total received */
    private function receivedBetween(Carbon $from, Carbon $to): Collection
    {
        return Payment::query()
            ->where('status', PaymentStatus::Paid)
            ->whereBetween('paid_at', [$from->copy()->utc(), $to->copy()->utc()])
            ->selectRaw('currency, SUM(amount) as total')
            ->groupBy('currency')
            ->pluck('total', 'currency')
            ->mapWithKeys(fn ($total, $currency): array => [strtoupper((string) $currency) => (float) $total]);
    }

    private function comparison(float $current, float $previous, string $currency): string
    {
        if ($previous <= 0) {
            return 'Nothing received last month';
        }

        $change = (int) round(($current - $previous) / $previous * 100);

        return ($change >= 0 ? '+' : '').$change.'% vs '.Money::format($previous, $currency).' last month';
    }
}
