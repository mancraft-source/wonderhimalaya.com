<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Destination;
use App\Models\Post;
use App\Models\Setting;
use App\Models\Testimonial;
use App\Models\Trip;
use App\Support\Seo;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class StaticController extends Controller
{
    public function tailorMade(): View
    {
        return view('site.tailor-made', [
            'seo' => Seo::make('Tailor-Made Private Treks & Tours | Wonder Himalaya', 'Plan a tailor-made private trek or tour in Nepal, Tibet or Bhutan. Tell us where, when and how you like to travel; a local specialist designs your itinerary.'),
        ]);
    }

    public function b2b(): View
    {
        return view('site.b2b', [
            'regions' => Destination::whereNull('parent_id')->with(['children', 'media'])->orderBy('sort')->get(),
            'testimonials' => Testimonial::where('is_published', true)->orderByDesc('is_featured')->limit(3)->get(),
            'stats' => Setting::get('stats', []),
            'seo' => Seo::make('Nepal, Tibet & Bhutan Ground Handler for Agencies', 'Ground handling in Nepal, Tibet and Bhutan for travel agencies and tour operators: white-label services, FIT, small-group expeditions and MICE from Kathmandu.'),
        ]);
    }

    public function contact(): View
    {
        return view('site.contact', [
            'seo' => Seo::make('Contact Us – Thamel, Kathmandu Office | Wonder Himalaya', 'Contact Wonder Himalaya by phone, WhatsApp or email, or visit our office in Thamel, Kathmandu, to plan a trek or tour in Nepal, Tibet or Bhutan with our team.'),
        ]);
    }

    public function reviews(): View
    {
        return view('site.reviews', [
            'testimonials' => Testimonial::where('is_published', true)->with('trip')->orderByDesc('is_featured')->latest('reviewed_at')->paginate(12),
            'reviews' => Setting::get('reviews', []),
            'social' => array_filter(Setting::get('social', [])),
            'seo' => Seo::make('Wonder Himalaya Reviews from Travellers', 'Read Wonder Himalaya reviews from travellers who trekked and toured with us in Nepal, Tibet and Bhutan, collected from Google and Tripadvisor.'),
        ]);
    }

    public function search(Request $request): View
    {
        $q = $request->string('q')->trim()->limit(80, '')->toString();
        $like = '%'.$q.'%';

        $seo = Seo::make($q !== '' ? 'Search: '.$q : 'Search', 'Search Wonder Himalaya treks, tours, destinations and travel guides across Nepal, Tibet and Bhutan.')->noindexFollow();
        $seo->noindex = true;

        return view('site.search', [
            'q' => $q,
            'trips' => $q === '' ? collect() : Trip::published()->where(fn ($w) => $w->where('title', 'like', $like)->orWhere('excerpt', 'like', $like))->with(['destination', 'difficulty', 'media'])->limit(12)->get(),
            'posts' => $q === '' ? collect() : Post::published()->where(fn ($w) => $w->where('title', 'like', $like)->orWhere('excerpt', 'like', $like))->with(['media', 'category'])->latest('published_at')->limit(6)->get(),
            'destinations' => $q === '' ? collect() : Destination::where('name', 'like', $like)->get(),
            'seo' => $seo,
        ]);
    }

    public function thanks(): View
    {
        $seo = Seo::make('Thank you');
        $seo->noindex = true;

        return view('site.thanks', ['seo' => $seo, 'reference' => session('lead_reference')]);
    }
}
