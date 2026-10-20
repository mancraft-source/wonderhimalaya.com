<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Leads\SubmitEnquiry;
use App\Http\Requests\EnquiryRules;
use App\Rules\Turnstile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @tags Enquiries
 */
class EnquiryController extends ApiController
{
    /**
     * Submit an enquiry (creates a CRM contact + lead).
     *
     * Rate limited per IP. When Cloudflare Turnstile is configured, unauthenticated requests must send
     * `turnstile_token`. `privacy_accepted` must be true.
     *
     * @unauthenticated
     */
    public function store(Request $request, SubmitEnquiry $submit): JsonResponse
    {
        $rules = EnquiryRules::rules();
        $rules['privacy_accepted'] = ['required', 'accepted'];

        $tokenAuthenticated = $request->bearerToken() && auth('sanctum')->check();

        if (! $tokenAuthenticated) {
            $rules['turnstile_token'] = Turnstile::rules();
        }

        $data = $request->validate($rules);
        unset($data['turnstile_token'], $data['privacy_accepted']);
        $data['source'] ??= 'api';

        $lead = $submit->handle($data, [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'referrer' => $request->headers->get('referer'),
        ]);

        return response()->json(['data' => ['reference' => $lead->reference, 'status' => $lead->status->value]], 201);
    }
}
