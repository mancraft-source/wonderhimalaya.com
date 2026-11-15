<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\Post;
use App\Models\TeamMember;
use App\Models\Trip;
use App\Support\Preview;
use Illuminate\Http\Response;

/**
 * Signed, expiring preview of an unpublished record: renders the normal public page for it
 * (with the preview bar and noindex from the site layout) for anyone holding the link.
 */
class PreviewController extends Controller
{
    public function __invoke(string $type, int $id): Response
    {
        $record = Preview::TYPES[$type]::query()->findOrFail($id);

        Preview::grant($record);

        $view = match (true) {
            $record instanceof Trip => app(TripController::class)->show($record->slug),
            $record instanceof Page => app(PageController::class)->show($record->path),
            $record instanceof Post => app(BlogController::class)->show($record->slug),
            $record instanceof TeamMember => app(TeamController::class)->show($record->slug),
        };

        return response($view)
            ->header('X-Robots-Tag', 'noindex, nofollow')
            ->header('Cache-Control', 'private, no-store');
    }
}
