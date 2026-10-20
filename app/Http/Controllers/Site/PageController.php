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
    use FindsPublished;

    public function show(string $path): View
    {
        $page = $this->scopeVisible(Page::query(), Permissions::CONTENT)->where('path', $path)->with(['parent', 'media'])->firstOrFail();

        $siblings = $page->parent_id
            ? Page::published()->where('parent_id', $page->parent_id)->orderBy('sort')->orderBy('title')->get(['id', 'title', 'path', 'slug'])
            : Page::published()->where('parent_id', $page->id)->orderBy('sort')->orderBy('title')->get(['id', 'title', 'path', 'slug']);

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
