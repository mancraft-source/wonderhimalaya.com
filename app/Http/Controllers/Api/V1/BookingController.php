<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\BookingStatus;
use App\Enums\PaymentType;
use App\Http\Resources\V1\BookingResource;
use App\Http\Resources\V1\PaymentResource;
use App\Models\Booking;
use App\Models\Payment;
use App\Payments\PaymentService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * @tags Bookings
 */
class BookingController extends ApiController
{
    /** Filters: `filter[status]`, `filter[trip]` (slug), `filter[starts_from]`, `filter[starts_to]` (Y-m-d), `filter[updated_since]`. */
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Booking::class);

        $bookings = QueryBuilder::for(Booking::query()->with(['trip', 'contact']))
            ->allowedFilters(
                AllowedFilter::exact('status'),
                AllowedFilter::callback('trip', fn (Builder $q, $value) => $q->whereHas('trip', fn ($t) => $t->whereIn('slug', (array) $value))),
                AllowedFilter::callback('starts_from', fn (Builder $q, $value) => $q->whereDate('start_date', '>=', LeadController::date($value))),
                AllowedFilter::callback('starts_to', fn (Builder $q, $value) => $q->whereDate('start_date', '<=', LeadController::date($value))),
                AllowedFilter::callback('updated_since', fn (Builder $q, $value) => $q->where('updated_at', '>=', LeadController::date($value))),
            )
            ->allowedSorts('start_date', 'created_at', 'updated_at')
            ->defaultSort('-created_at')
            ->paginate($this->perPage($request, 25))
            ->withQueryString();

        return BookingResource::collection($bookings);
    }

    /** Booking with contact, travellers (passport masked) and payments. */
    public function show(string $number): BookingResource
    {
        $booking = Booking::where('number', $number)->firstOrFail();
        Gate::authorize('view', $booking);

        return new BookingResource($booking->load(['trip', 'tier', 'departure', 'contact', 'travellers', 'payments']));
    }

    /**
     * Create a pending payment link.
     *
     * `type`: `balance` (default — the outstanding balance), `deposit` (the booking deposit) or `custom` with `amount`
     * (at most the outstanding balance).
     */
    public function storePaymentLink(Request $request, string $number, PaymentService $payments): JsonResponse
    {
        $booking = Booking::where('number', $number)->firstOrFail();
        Gate::authorize('update', $booking);
        Gate::authorize('create', Payment::class);

        abort_if($booking->status === BookingStatus::Cancelled, 422, 'Booking is cancelled.');

        $data = $request->validate([
            'type' => ['nullable', Rule::enum(PaymentType::class)],
            'amount' => ['nullable', 'required_if:type,custom', 'numeric', 'min:1'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $type = PaymentType::tryFrom($data['type'] ?? '') ?? PaymentType::Balance;
        $balance = $booking->balanceDue();

        $amount = match ($type) {
            PaymentType::Deposit => min((float) $booking->deposit_amount, $balance),
            PaymentType::Balance => $balance,
            PaymentType::Custom => (float) $data['amount'],
        };

        if ($amount <= 0 || $amount > $balance + 0.001) {
            abort(422, 'Amount must be greater than zero and not exceed the outstanding balance.');
        }

        $payment = $payments->createLink($booking, $amount, $type, $request->user(), $data['description'] ?? null);

        return (new PaymentResource($payment))->response()->setStatusCode(201);
    }
}
