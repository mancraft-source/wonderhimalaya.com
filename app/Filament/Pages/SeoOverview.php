<?php

namespace App\Filament\Pages;

use App\Console\Commands\SeoScore;
use App\Models\NotFoundLog;
use App\Models\Redirect;
use App\Support\Permissions;
use App\Support\Seo\SeoAnalyzer;
use App\Support\Seo\SeoSettings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use UnitEnum;

/** SEO → Overview: site-wide SEO health, the records that most need work, duplicate titles and recent 404s. */
class SeoOverview extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPresentationChartLine;

    protected static string|UnitEnum|null $navigationGroup = 'SEO';

    protected static ?string $navigationLabel = 'Overview';

    protected static ?int $navigationSort = 5;

    protected static ?string $title = 'SEO overview';

    protected static ?string $slug = 'seo';

    protected string $view = 'filament.pages.seo-overview';

    public static function canAccess(): bool
    {
        return auth()->user()?->can(Permissions::CONTENT) ?? false;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('rescore')
                ->label('Re-run analysis')
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('gray')
                ->action(fn () => Notification::make()->title('Analysed '.SeoScore::recalculate().' pages')->success()->send()),
            Action::make('sitemap')->label('Sitemap')->icon(Heroicon::OutlinedMap)->color('gray')->url(url('/sitemap.xml'), shouldOpenInNewTab: true),
        ];
    }

    /** @return array<string, mixed> */
    protected function getViewData(): array
    {
        $records = $this->records();
        $titles = $records->groupBy('title')->filter(fn (Collection $group) => $group->count() > 1);

        return [
            'indexable' => SeoSettings::indexable(),
            'total' => $records->count(),
            'average' => (int) round($records->avg('score') ?? 0),
            'bands' => [
                'good' => $records->where('score', '>=', 75)->count(),
                'ok' => $records->whereBetween('score', [50, 74])->count(),
                'bad' => $records->where('score', '<', 50)->count(),
            ],
            'inSitemap' => $records->where('inSitemap', true)->count(),
            'noindex' => $records->where('noindex', true)->count(),
            'noDescription' => $records->whereNull('description')->count(),
            'noKeyphrase' => $records->where('keyphrase', false)->count(),
            'attention' => $records->sortBy('score')->take(15)->values(),
            'duplicates' => $titles,
            'notFound' => NotFoundLog::active()->orderByDesc('hits')->limit(8)->get(),
            'notFoundCount' => NotFoundLog::active()->count(),
            'redirectCount' => Redirect::where('is_active', true)->count(),
        ];
    }

    /** @return Collection<int, array<string, mixed>> */
    private function records(): Collection
    {
        return collect(SeoScore::MODELS)->flatMap(function (string $class) {
            $type = (new $class)->seoType();
            $resource = Filament::getModelResource($class);

            $with = ['trip' => ['destination', 'difficulty'], 'post' => ['category', 'author'], 'page' => ['parent'], 'destination' => ['parent']][$type] ?? [];

            return $class::query()->with($with)->get()->map(function (Model $model) use ($type, $resource): array {
                $analysis = $model->seoAnalysis();
                $firstIssue = collect($analysis['checks'])->firstWhere('status', SeoAnalyzer::BAD) ?? collect($analysis['checks'])->firstWhere('status', SeoAnalyzer::OK);

                return [
                    'type' => SeoSettings::TYPES[$type],
                    'name' => $model->title ?? $model->name,
                    'title' => $model->seoTitle(),
                    'description' => $model->seoDescription(),
                    'score' => $analysis['score'],
                    'issue' => $firstIssue['label'] ?? null,
                    'noindex' => $model->seoNoindex(),
                    'inSitemap' => $model->seoInSitemap(),
                    'keyphrase' => filled($model->seo['focus_keyphrase'] ?? null),
                    'editUrl' => $resource ? $resource::getUrl('edit', ['record' => $model]) : null,
                ];
            });
        });
    }
}
