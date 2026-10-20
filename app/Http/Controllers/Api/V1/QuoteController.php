<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\V1\QuoteResource;
use App\Models\Quote;
use Illuminate\Support\Facades\Gate;

/**
 * @tags Quotes
 */
class QuoteController extends ApiController
{
    public function show(string $number): QuoteResource
    {
        $quote = Quote::where('number', $number)->firstOrFail();
        Gate::authorize('view', $quote);

        return new QuoteResource($quote->load(['items', 'trip', 'contact']));
    }
}
