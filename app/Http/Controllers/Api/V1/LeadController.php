<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Leads\SubmitEnquiry;
use App\Enums\LeadStatus;
use App\Http\Requests\EnquiryRules;
use App\Http\Resources\V1\LeadResource;
use App\Http\Resources\V1\NoteResource;
use App\Models\Lead;
use App\Models\User;
use App\Support\Permissions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * @tags Leads
 */
class LeadController extends ApiController
{
    /**
     * List leads visible to the token owner (sales: own + unassigned; managers: all).
     *
     * Filters: `filter[status]`, `filter[type]` (comma separated), `filter[owner]` (user id, `me` or `none`),
     * `filter[updated_since]` (ISO-8601). Sorts: `created_at`, `updated_at`, `next_follow_up_at`.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Lead::class);

        $user = $request->user();

        $leads = QueryBuilder::for(Lead::query()->with(['contact', 'trip', 'owner'])->tap(fn (Builder $q) => self::scopeVisible($q, $user)))
            ->allowedFilters(
                AllowedFilter::exact('status'),
                AllowedFilter::exact('type'),
                AllowedFilter::callback('owner', function (Builder $q, $value) use ($user) {
                    $value = is_array($value) ? reset($value) : $value;
                    match ((string) $value) {
                        'me' => $q->where('owner_id', $user->id),
                        'none' => $q->whereNull('owner_id'),
                        default => $q->where('owner_id', (int) $value),
                    };
                }),
                AllowedFilter::callback('updated_since', fn (Builder $q, $value) => $q->where('updated_at', '>=', self::date($value))),
            )
            ->allowedSorts('created_at', 'updated_at', 'next_follow_up_at')
            ->defaultSort('-created_at')
            ->paginate($this->perPage($request, 25))
            ->withQueryString();

        return LeadResource::collection($leads);
    }

    /** Lead with contact and notes. */
    public function show(Lead $lead): LeadResource
    {
        Gate::authorize('view', $lead);

        return new LeadResource($lead->load(['contact.company', 'trip', 'owner', 'notes.user']));
    }

    /** Create a lead from an external system (same fields as the public enquiry; source defaults to `api`). */
    public function store(Request $request, SubmitEnquiry $submit): JsonResponse
    {
        Gate::authorize('create', Lead::class);

        $rules = EnquiryRules::rules();
        $rules['privacy_accepted'] = ['nullable', 'boolean'];
        $rules['start_date'] = ['nullable', 'date'];

        $data = $request->validate($rules);
        unset($data['privacy_accepted']);
        $data['source'] ??= 'api';

        $lead = $submit->handle($data, ['ip' => null, 'user_agent' => 'api:'.$request->user()->id]);

        return (new LeadResource($lead->load(['contact', 'trip'])))->response()->setStatusCode(201);
    }

    /** Update status, owner or next follow-up. */
    public function update(Request $request, Lead $lead): LeadResource
    {
        Gate::authorize('update', $lead);

        $data = $request->validate([
            'status' => ['sometimes', Rule::enum(LeadStatus::class)],
            'owner_id' => ['sometimes', 'nullable', 'integer', Rule::exists('users', 'id')->where('is_active', true)],
            'next_follow_up_at' => ['sometimes', 'nullable', 'date'],
            'lost_reason' => ['sometimes', 'nullable', 'string', 'max:255', 'required_if:status,lost'],
        ]);

        $user = $request->user();

        // Only managers may hand leads to someone else; sales may claim/release their own.
        if (array_key_exists('owner_id', $data) && $data['owner_id'] !== null && (int) $data['owner_id'] !== $user->id) {
            abort_unless($user->can(Permissions::CRM_VIEW_ALL), 403, 'You may only assign leads to yourself.');
        }

        $lead->update($data);

        return new LeadResource($lead->refresh()->load(['contact', 'trip', 'owner']));
    }

    /** Add a note to a lead. */
    public function storeNote(Request $request, Lead $lead): JsonResponse
    {
        Gate::authorize('update', $lead);

        $data = $request->validate(['body' => ['required', 'string', 'max:5000']]);

        $note = $lead->notes()->create(['body' => $data['body'], 'user_id' => $request->user()->id]);

        return (new NoteResource($note->load('user')))->response()->setStatusCode(201);
    }

    public static function scopeVisible(Builder $query, User $user): void
    {
        if (! $user->can(Permissions::CRM_VIEW_ALL)) {
            $query->where(fn (Builder $q) => $q->whereNull('owner_id')->orWhere('owner_id', $user->id));
        }
    }

    public static function date(mixed $value): Carbon
    {
        try {
            return Carbon::parse(is_array($value) ? implode(',', $value) : (string) $value);
        } catch (\Throwable) {
            abort(422, 'Invalid date.');
        }
    }
}
