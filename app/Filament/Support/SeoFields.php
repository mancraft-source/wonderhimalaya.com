<?php

namespace App\Filament\Support;

use App\Support\Seo\SeoAnalyzer;
use App\Support\Seo\SeoSettings;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\View;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

/**
 * The Yoast-style SEO panel shared by every content form (trips, destinations, activities, pages, posts, team):
 * live Google preview, focus keyphrase + analysis, social sharing overrides and indexing controls.
 */
final class SeoFields
{
    /** Form attributes that feed the title/description templates and the content checks. */
    private const PREVIEW_ATTRIBUTES = ['title', 'name', 'slug', 'excerpt', 'intro', 'tagline', 'role', 'duration_days', 'price_from', 'sale_price', 'max_altitude_m', 'trip_code', 'overview', 'description', 'body', 'bio', 'blocks', 'published_at'];

    /** @param  class-string<Model>  $modelClass  a model using HasSeo */
    public static function section(string $modelClass): Section
    {
        $type = (new $modelClass)->seoType();
        $defaults = SeoSettings::type($type);

        return Section::make('SEO')
            ->description('How this page appears in Google and when shared. Blank fields use the defaults under SEO → SEO settings.')
            ->icon(Heroicon::OutlinedMagnifyingGlass)
            ->collapsible()
            ->columnSpanFull()
            ->schema([
                Tabs::make('SEO tabs')
                    ->contained(false)
                    ->tabs([
                        Tab::make('Search appearance')
                            ->icon(Heroicon::OutlinedGlobeAlt)
                            ->schema([
                                View::make('filament.seo.serp-preview')
                                    ->viewData(fn (Get $get, ?Model $record): array => self::preview($modelClass, $get, $record)),
                                TextInput::make('seo.focus_keyphrase')
                                    ->label('Focus keyphrase')
                                    ->placeholder('e.g. everest base camp trek')
                                    ->maxLength(100)
                                    ->live(onBlur: true)
                                    ->helperText('The search term this page should rank for. The Analysis tab checks the page against it.'),
                                TextInput::make('seo.title')
                                    ->label('SEO title')
                                    ->placeholder($defaults['title'])
                                    ->maxLength(120)
                                    ->live(debounce: 600)
                                    ->helperText(self::variablesHelp($type)),
                                Textarea::make('seo.description')
                                    ->label('Meta description')
                                    ->placeholder($defaults['description'] === '%%excerpt%%' ? 'Uses the excerpt when left blank' : $defaults['description'])
                                    ->rows(3)
                                    ->maxLength(320)
                                    ->live(debounce: 600)
                                    ->helperText('Aim for 120–160 characters that sell the click. Variables work here too.'),
                            ]),
                        Tab::make('Analysis')
                            ->icon(Heroicon::OutlinedChartBar)
                            ->badge(fn (Get $get, ?Model $record): ?string => (string) self::preview($modelClass, $get, $record)['analysis']['score'])
                            ->badgeColor(fn (Get $get, ?Model $record): string => SeoAnalyzer::rating(self::preview($modelClass, $get, $record)['analysis']['score']))
                            ->schema([
                                View::make('filament.seo.analysis')
                                    ->viewData(fn (Get $get, ?Model $record): array => self::preview($modelClass, $get, $record)),
                            ]),
                        Tab::make('Social')
                            ->icon(Heroicon::OutlinedShare)
                            ->schema([
                                TextInput::make('seo.og_title')
                                    ->label('Social title')
                                    ->placeholder('Uses the SEO title')
                                    ->maxLength(100)
                                    ->live(debounce: 600),
                                Textarea::make('seo.og_description')
                                    ->label('Social description')
                                    ->placeholder('Uses the meta description')
                                    ->rows(2)
                                    ->maxLength(300)
                                    ->live(debounce: 600),
                                FileUpload::make('seo.og_image')
                                    ->label('Social image')
                                    ->helperText('Shown when the page is shared on Facebook, WhatsApp, LinkedIn or X. 1200 × 630 px works best. Uses the main photo when empty.')
                                    ->image()
                                    ->disk('public')
                                    ->directory('seo')
                                    ->visibility('public')
                                    ->maxSize(4096)
                                    ->imageEditor()
                                    ->imageEditorAspectRatioOptions(['1.91:1']),
                                View::make('filament.seo.social-preview')
                                    ->viewData(fn (Get $get, ?Model $record): array => self::preview($modelClass, $get, $record)),
                            ]),
                        Tab::make('Advanced')
                            ->icon(Heroicon::OutlinedAdjustmentsHorizontal)
                            ->columns(2)
                            ->schema([
                                Toggle::make('seo.noindex')
                                    ->label('Hide from search engines (noindex)')
                                    ->helperText($defaults['noindex'] ? 'All '.strtolower(SeoSettings::TYPES[$type]).' are already set to noindex in SEO settings.' : 'Also removes the page from the sitemap.'),
                                Toggle::make('seo.nofollow')
                                    ->label('Ask search engines not to follow links (nofollow)'),
                                Toggle::make('seo.exclude_from_sitemap')
                                    ->label('Leave out of the XML sitemap')
                                    ->helperText('The page can still be indexed if linked from elsewhere.'),
                                TextInput::make('seo.canonical')
                                    ->label('Canonical URL')
                                    ->url()
                                    ->maxLength(500)
                                    ->placeholder('This page’s own URL')
                                    ->helperText('Only when this page duplicates another one. A page with a custom canonical is left out of the sitemap.'),
                            ]),
                    ]),
            ]);
    }

    /**
     * Resolved title/description/URL and analysis for the current (unsaved) form state.
     *
     * @param  class-string<Model>  $modelClass
     * @return array{title: string, description: ?string, url: string, image: ?string, analysis: array{score: int, checks: array<int, array{status: string, label: string}>}, ogTitle: string, ogDescription: ?string}
     */
    public static function preview(string $modelClass, Get $get, ?Model $record): array
    {
        $state = [];
        foreach ([...self::PREVIEW_ATTRIBUTES, 'seo'] as $attribute) {
            $state[$attribute] = $get($attribute);
        }
        $key = $modelClass.':'.$record?->getKey().':'.md5(serialize(array_map(fn ($v) => is_object($v) ? spl_object_id($v) : $v, array_filter($state, fn ($v) => ! is_object($v)))));

        return self::$memo[$key] ??= self::buildPreview($modelClass, $state, $record);
    }

    /** @var array<string, array<string, mixed>> */
    private static array $memo = [];

    /**
     * @param  class-string<Model>  $modelClass
     * @param  array<string, mixed>  $state
     * @return array<string, mixed>
     */
    private static function buildPreview(string $modelClass, array $state, ?Model $record): array
    {
        $get = fn (string $attribute): mixed => $state[$attribute] ?? null;
        $model = $record ? clone $record : new $modelClass;

        foreach (self::PREVIEW_ATTRIBUTES as $attribute) {
            $value = $get($attribute);
            if (is_string($value) || is_numeric($value) || (is_array($value) && $attribute === 'blocks')) {
                $model->setAttribute($attribute, $value);
            }
        }
        $model->setAttribute('seo', array_filter((array) $get('seo'), fn ($v) => $v !== null && $v !== ''));

        try {
            $url = url($model->publicPath());
        } catch (\Throwable) {
            $url = url('/'.($model->slug ?? ''));
        }

        $title = $model->seoTitle();
        $description = $model->seoDescription();
        $ogImage = $model->seo['og_image'] ?? null;
        $ogImage = is_array($ogImage) ? reset($ogImage) : $ogImage;
        if (is_object($ogImage) && method_exists($ogImage, 'temporaryUrl')) {
            try {
                $ogImage = $ogImage->temporaryUrl();
            } catch (\Throwable) {
                $ogImage = null;
            }
        }

        return [
            'title' => $title,
            'description' => $description,
            'url' => $url,
            'image' => is_string($ogImage) ? (str_starts_with($ogImage, 'http') ? $ogImage : asset('storage/'.$ogImage)) : ($record && method_exists($record, 'getFirstMediaUrl') ? ($record->getFirstMediaUrl('hero') ?: $record->getFirstMediaUrl('photo') ?: null) : null),
            'ogTitle' => $model->seo['og_title'] ?? $title,
            'ogDescription' => $model->seo['og_description'] ?? $description,
            'analysis' => $model->seoAnalysis(),
        ];
    }

    public static function variablesHelp(string $type): HtmlString
    {
        $variables = [...SeoSettings::VARIABLES['*'], ...(SeoSettings::VARIABLES[$type] ?? [])];
        $chips = collect($variables)->map(fn ($label, $variable) => '<code title="'.e($label).'" class="rounded bg-gray-100 px-1 py-0.5 text-xs dark:bg-white/10">'.e($variable).'</code>')->implode(' ');

        return new HtmlString('Leave blank for the default. Variables: '.$chips);
    }

    public static function scoreColumn(): TextColumn
    {
        return TextColumn::make('seo.score')
            ->label('SEO')
            ->badge()
            ->color(fn (?int $state): string => SeoAnalyzer::rating($state))
            ->placeholder('—')
            ->tooltip('SEO score — open the record’s SEO → Analysis tab for details')
            ->toggleable();
    }

    public static function scoreFilter(): SelectFilter
    {
        return SelectFilter::make('seo_health')
            ->label('SEO')
            ->options([
                'needs_work' => 'Needs work (under 50)',
                'ok' => 'OK (50–74)',
                'good' => 'Good (75+)',
                'no_keyphrase' => 'No focus keyphrase',
                'noindex' => 'Hidden from search (noindex)',
            ])
            ->query(fn (Builder $query, array $data): Builder => match ($data['value'] ?? null) {
                'needs_work' => $query->where('seo->score', '<', 50),
                'ok' => $query->where('seo->score', '>=', 50)->where('seo->score', '<', 75),
                'good' => $query->where('seo->score', '>=', 75),
                'no_keyphrase' => $query->where(fn (Builder $q) => $q->whereNull('seo->focus_keyphrase')->orWhere('seo->focus_keyphrase', '')),
                'noindex' => $query->where('seo->noindex', true),
                default => $query,
            });
    }
}
