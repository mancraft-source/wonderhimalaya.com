<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\V1\PageResource;
use App\Models\Page;

/**
 * @tags Pages
 */
class PageController extends ApiController
{
    /** A published CMS page by its full path, e.g. `about-us` or `travel-guide/nepal-visa`. */
    public function show(string $path): PageResource
    {
        $page = Page::query()->published()->where('path', trim($path, '/'))->with('media')->firstOrFail();

        return new PageResource($page);
    }
}
