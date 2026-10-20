<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

abstract class ApiController extends Controller
{
    protected const MAX_PER_PAGE = 50;

    protected function perPage(Request $request, int $default = 12): int
    {
        return max(1, min(self::MAX_PER_PAGE, (int) $request->integer('per_page', $default)));
    }
}
