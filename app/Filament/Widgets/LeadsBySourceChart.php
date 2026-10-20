<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Leads\LeadResource;
use App\Models\Lead;
use App\Support\Permissions;
use Filament\Widgets\ChartWidget;

class LeadsBySourceChart extends ChartWidget
{
    protected static ?int $sort = 3;

    protected ?string $heading = 'Leads by source';

    protected ?string $description = 'Last 90 days';

    protected ?string $maxHeight = '260px';

    private const PALETTE = ['#1F3A33', '#3F7D6B', '#D97706', '#0EA5E9', '#A855F7', '#E11D48', '#64748B', '#84CC16'];

    public static function canView(): bool
    {
        return (bool) auth()->user()?->can(Permissions::CRM_VIEW);
    }

    protected function getData(): array
    {
        $counts = LeadResource::scopeVisibleTo(Lead::query(), auth()->user())
            ->where('created_at', '>=', now()->subDays(90))
            ->pluck('source')
            ->map(fn (?string $source) => filled($source) ? ucfirst($source) : 'Unknown')
            ->countBy()
            ->sortDesc();

        if ($counts->count() > 7) {
            $counts = $counts->take(7)->put('Other', $counts->slice(7)->sum());
        }

        return [
            'datasets' => [[
                'label' => 'Leads',
                'data' => $counts->values()->all(),
                'backgroundColor' => array_slice(self::PALETTE, 0, max(1, $counts->count())),
            ]],
            'labels' => $counts->keys()->all(),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => ['legend' => ['position' => 'bottom']],
            'scales' => ['x' => ['display' => false], 'y' => ['display' => false]],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
