<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Site\Concerns\FindsPublished;
use App\Models\Page;
use App\Support\Permissions;
use App\Support\Seo;
use Illuminate\Contracts\View\View;

class PageController extends Controller
{
    /** Reading order of the top-level policy pages: booking first, legal small print last. */
    private const POLICY_ORDER = ['how-to-book-a-trip-with-wonder-himalaya', 'regarding-payment', 'risk-free-booking', 'terms-and-conditions', 'alerts', 'privacy-policy'];

    use FindsPublished;

    public function show(string $path): View
    {
        $page = $this->scopeVisible(Page::query(), Permissions::CONTENT)->where('path', $path)->with(['parent', 'media'])->firstOrFail();

        $siblings = match (true) {
            (bool) $page->parent_id => Page::published()->where('parent_id', $page->parent_id)->orderBy('sort')->orderBy('title')->get(['id', 'title', 'path', 'slug']),
            // Top-level policy pages (booking, payment, terms…) form one "Booking & policies" section.
            $page->template === 'legal' => Page::published()->whereNull('parent_id')->where('template', 'legal')->get(['id', 'title', 'path', 'slug', 'sort'])
                ->sortBy(fn (Page $p) => [array_search($p->path, self::POLICY_ORDER, true) === false ? 99 : array_search($p->path, self::POLICY_ORDER, true), $p->sort, $p->title])->values(),
            default => Page::published()->where('parent_id', $page->id)->orderBy('sort')->orderBy('title')->get(['id', 'title', 'path', 'slug']),
        };

        $hero = $page->getFirstMedia('hero');

        $view = view()->exists('site.templates.'.$page->template) ? 'site.templates.'.$page->template : 'site.page';

        return view($view, [
            'page' => $page,
            'siblings' => $siblings,
            'seo' => Seo::for($page, $page->title, $page->excerpt ?: $this->firstText($page), $hero?->getUrl()),
        ]);
    }

    private function firstText(Page $page): ?string
    {
        foreach ($page->blocks ?? [] as $block) {
            $text = trim(strip_tags((string) ($block['data']['content'] ?? $block['data']['text'] ?? $block['data']['subheading'] ?? '')));
            if ($text !== '') {
                return $text;
            }
        }

        return null;
    }
}
