<?php

namespace App\Filament\Widgets;

use App\Enums\LeadStatus;
use App\Filament\Resources\Leads\LeadResource;
use App\Models\Lead;
use App\Support\Permissions;
use Filament\Widgets\ChartWidget;

class LeadsTrendChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected ?string $heading = 'Leads per week';

    protected ?string $description = 'Last 12 weeks';

    protected ?string $maxHeight = '260px';

    public static function canView(): bool
    {
        return (bool) auth()->user()?->can(Permissions::CRM_VIEW);
    }

    protected function getData(): array
    {
        $start = now()->startOfWeek()->subWeeks(11);

        $leads = LeadResource::scopeVisibleTo(Lead::query(), auth()->user())
            ->where('created_at', '>=', $start)
            ->get(['created_at', 'status']);

        $weeks = collect(range(0, 11))->map(fn (int $i) => $start->copy()->addWeeks($i));
        $bucket = fn ($date) => $date->copy()->startOfWeek()->toDateString();

        $all = $leads->countBy(fn (Lead $lead) => $bucket($lead->created_at));
        $won = $leads
            ->filter(fn (Lead $lead) => in_array($lead->status, [LeadStatus::DepositPaid, LeadStatus::Confirmed, LeadStatus::Travelled], true))
            ->countBy(fn (Lead $lead) => $bucket($lead->created_at));

        return [
            'datasets' => [
                [
                    'label' => 'Leads',
                    'data' => $weeks->map(fn ($w) => (int) ($all[$w->toDateString()] ?? 0))->all(),
                    'borderColor' => '#1F3A33',
                    'backgroundColor' => 'rgba(31, 58, 51, 0.15)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
                [
                    'label' => 'Won',
                    'data' => $weeks->map(fn ($w) => (int) ($won[$w->toDateString()] ?? 0))->all(),
                    'borderColor' => '#D97706',
                    'backgroundColor' => 'rgba(217, 119, 6, 0.1)',
                    'tension' => 0.3,
                ],
            ],
            'labels' => $weeks->map(fn ($w) => $w->format('j M'))->all(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
