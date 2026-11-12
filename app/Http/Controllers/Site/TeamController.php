<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\TeamMember;
use App\Support\Seo;
use Illuminate\Contracts\View\View;

class TeamController extends Controller
{
    public function index(): View
    {
        $members = TeamMember::where('is_published', true)->with('media')->orderBy('sort')->orderBy('name')->get();

        return view('site.team.index', [
            'leaders' => $members->where('is_guide', false)->values(),
            'guides' => $members->where('is_guide', true)->values(),
            'seo' => Seo::make('Our Team – Guides & Trip Specialists | Wonder Himalaya', 'Meet the Wonder Himalaya team: the founders and trip specialists in our Thamel, Kathmandu office and the licensed trekking guides who lead our trips.'),
        ]);
    }

    public function show(string $slug): View
    {
        $member = TeamMember::where('slug', $slug)->where('is_published', true)->with('media')->firstOrFail();

        return view('site.team.show', [
            'member' => $member,
            'others' => TeamMember::where('is_published', true)->whereKeyNot($member->id)->with('media')->inRandomOrder()->limit(4)->get(),
            'seo' => Seo::for($member, $member->name.($member->role ? ' — '.$member->role : ''), $member->excerpt ?: strip_tags((string) $member->bio), $member->getFirstMediaUrl('photo') ?: null)
                ->withSchema(['@type' => 'Person', 'name' => $member->name, 'jobTitle' => $member->role, 'worksFor' => ['@id' => url('/').'#organization']]),
        ]);
    }
}
