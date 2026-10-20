<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Destination;
use App\Models\Setting;
use App\Models\Testimonial;
use App\Support\Seo;
use Illuminate\Contracts\View\View;

class StaticController extends Controller
{
    public function tailorMade(): View
    {
        return view('site.tailor-made', [
            'seo' => Seo::make('Plan a tailor-made Himalayan journey', 'Tell us where, when and how you like to travel — a local trip specialist will design a private itinerary for you within 24 hours.'),
        ]);
    }

    public function b2b(): View
    {
        return view('site.b2b', [
            'regions' => Destination::whereNull('parent_id')->with(['children', 'media'])->orderBy('sort')->get(),
            'testimonials' => Testimonial::where('is_published', true)->orderByDesc('is_featured')->limit(3)->get(),
            'stats' => Setting::get('stats', []),
            'seo' => Seo::make('B2B — your ground partner in Nepal, Tibet & Bhutan', 'White-label ground handling, FIT curation, small-group expeditions and MICE for travel agencies and tour operators. Licensed, insured, locally owned in Kathmandu.'),
        ]);
    }

    public function contact(): View
    {
        return view('site.contact', [
            'seo' => Seo::make('Contact our Kathmandu team', 'Call, WhatsApp or email Wonder Himalaya in Thamel, Kathmandu. Trip specialists reply within 24 hours.'),
        ]);
    }

    public function thanks(): View
    {
        $seo = Seo::make('Thank you');
        $seo->noindex = true;

        return view('site.thanks', ['seo' => $seo, 'reference' => session('lead_reference')]);
    }
}
