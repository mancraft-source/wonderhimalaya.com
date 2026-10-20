<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Site\Concerns\FindsPublished;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Trip;
use App\Support\Permissions;
use App\Support\Seo;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    use FindsPublished;

    public function index(Request $request): View
    {
        return $this->listing(null, $request);
    }

    public function category(string $slug, Request $request): View
    {
        return $this->listing(PostCategory::where('slug', $slug)->firstOrFail(), $request);
    }

    private function listing(?PostCategory $category, Request $request): View
    {
        $posts = Post::published()
            ->with(['media', 'category', 'author'])
            ->when($category, fn ($q) => $q->where('post_category_id', $category->id))
            ->when($request->string('q')->trim()->isNotEmpty(), fn ($q) => $q->where('title', 'like', '%'.$request->string('q')->trim()->limit(80, '').'%'))
            ->latest('published_at')
            ->paginate(13)
            ->withQueryString();

        return view('site.blog.index', [
            'posts' => $posts,
            'category' => $category,
            'categories' => PostCategory::withCount(['posts' => fn ($q) => $q->published()])->get()->where('posts_count', '>', 0),
            'seo' => Seo::make(
                $category ? $category->name.' — Journal' : 'The Journal — stories & advice from the trail',
                $category?->description ?: 'Trek guides, route intel, culture and stories from the Himalaya, written by our Kathmandu team and guides.',
            ),
        ]);
    }

    public function show(string $slug): View
    {
        $post = $this->scopeVisible(Post::query(), Permissions::CONTENT)->where('slug', $slug)->with(['media', 'category', 'author.media', 'tags'])->firstOrFail();

        $hero = $post->getFirstMedia('hero');
        $seo = Seo::for($post, $post->title, $post->excerpt ?: strip_tags((string) $post->body), $hero?->getUrl())->type('article')
            ->withSchema([
                '@type' => 'BlogPosting',
                'headline' => $post->title,
                'image' => $hero?->getUrl(),
                'datePublished' => $post->published_at?->toIso8601String(),
                'dateModified' => $post->updated_at?->toIso8601String(),
                'author' => $post->author ? ['@type' => 'Person', 'name' => $post->author->name] : ['@id' => url('/').'#organization'],
                'publisher' => ['@id' => url('/').'#organization'],
                'mainEntityOfPage' => url($post->publicPath()),
            ]);

        return view('site.blog.show', [
            'post' => $post,
            'seo' => $seo,
            'more' => Post::published()->whereKeyNot($post->id)->with(['media', 'category'])
                ->when($post->post_category_id, fn ($q) => $q->orderByRaw('post_category_id = ? desc', [$post->post_category_id]))
                ->latest('published_at')->limit(3)->get(),
            'suggestedTrips' => Trip::published()->where('is_featured', true)->with(['destination', 'difficulty', 'media'])->limit(2)->get(),
        ]);
    }
}
