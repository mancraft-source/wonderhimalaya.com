<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\V1\ContactResource;
use App\Models\Contact;
use App\Support\Permissions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * @tags Contacts
 */
class ContactController extends ApiController
{
    /** Filters: `filter[search]` (name/e-mail), `filter[email]`, `filter[updated_since]`. */
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Contact::class);

        $contacts = QueryBuilder::for(Contact::query()->with('company'))
            ->allowedFilters(
                AllowedFilter::exact('email'),
                AllowedFilter::callback('search', fn (Builder $q, $value) => $q->where(function (Builder $w) use ($value) {
                    $term = '%'.TripController::like(implode(' ', (array) $value)).'%';
                    $w->where('first_name', 'like', $term)->orWhere('last_name', 'like', $term)->orWhere('email', 'like', $term);
                })),
                AllowedFilter::callback('updated_since', fn (Builder $q, $value) => $q->where('updated_at', '>=', LeadController::date($value))),
            )
            ->allowedSorts('created_at', 'updated_at', 'last_name')
            ->defaultSort('-created_at')
            ->paginate($this->perPage($request, 25))
            ->withQueryString();

        return ContactResource::collection($contacts);
    }

    /** Contact with their (visible) leads and bookings. */
    public function show(Request $request, Contact $contact): ContactResource
    {
        Gate::authorize('view', $contact);

        $user = $request->user();
        $contact->load([
            'company',
            'leads' => fn ($q) => $user->can(Permissions::CRM_VIEW_ALL) ? $q : $q->where(fn ($w) => $w->whereNull('owner_id')->orWhere('owner_id', $user->id)),
            'bookings.trip',
        ]);

        return new ContactResource($contact);
    }
}
