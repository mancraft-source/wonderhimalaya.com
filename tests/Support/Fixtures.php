<?php

namespace Tests\Support;

use App\Enums\BookingStatus;
use App\Enums\LeadStatus;
use App\Enums\LeadType;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Enums\PublishStatus;
use App\Enums\QuoteStatus;
use App\Models\Booking;
use App\Models\Contact;
use App\Models\Destination;
use App\Models\Lead;
use App\Models\Payment;
use App\Models\Quote;
use App\Models\Trip;
use App\Models\TripDeparture;
use App\Models\User;
use App\Support\Permissions;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/** Minimal, factory-independent test data for the API / payments suites. */
final class Fixtures
{
    public static function roles(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (Permissions::all() as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        foreach (Permissions::roles() as $role => $permissions) {
            Role::findOrCreate($role, 'web')->syncPermissions($permissions);
        }
    }

    public static function user(string $role = 'sales', array $attributes = []): User
    {
        self::roles();

        $user = User::create(array_merge([
            'name' => ucfirst($role).' '.Str::random(4),
            'email' => $role.'-'.Str::random(6).'@example.test',
            'password' => 'password',
            'is_active' => true,
        ], $attributes));

        $user->assignRole($role);

        return $user->fresh();
    }

    public static function trip(array $attributes = []): Trip
    {
        return Trip::create(array_merge([
            'title' => 'Trek '.Str::random(5),
            'slug' => 'trek-'.Str::lower(Str::random(8)),
            'status' => PublishStatus::Published,
            'published_at' => now()->subDay(),
            'duration_days' => 12,
            'price_from' => 1500,
            'currency' => 'USD',
        ], $attributes));
    }

    public static function destination(array $attributes = []): Destination
    {
        return Destination::create(array_merge([
            'name' => 'Region '.Str::random(4),
            'slug' => 'region-'.Str::lower(Str::random(6)),
        ], $attributes));
    }

    public static function departure(Trip $trip, array $attributes = []): TripDeparture
    {
        return TripDeparture::create(array_merge([
            'trip_id' => $trip->id,
            'start_date' => now()->addMonths(2)->startOfMonth()->addDays(4),
            'end_date' => now()->addMonths(2)->startOfMonth()->addDays(16),
            'seats_total' => 12,
            'seats_booked' => 0,
            'status' => 'available',
        ], $attributes));
    }

    public static function contact(array $attributes = []): Contact
    {
        return Contact::create(array_merge([
            'first_name' => 'Ana',
            'last_name' => 'Traveller',
            'email' => 'ana-'.Str::lower(Str::random(6)).'@example.test',
        ], $attributes));
    }

    public static function lead(array $attributes = []): Lead
    {
        return Lead::create(array_merge([
            'type' => LeadType::TripEnquiry,
            'status' => LeadStatus::New,
            'contact_id' => $attributes['contact_id'] ?? self::contact()->id,
            'ip_address' => '203.0.113.9',
        ], $attributes));
    }

    public static function quote(array $attributes = [], ?Trip $trip = null, ?TripDeparture $departure = null): Quote
    {
        $trip ??= self::trip();
        $departure ??= self::departure($trip);
        $contact = self::contact();
        $lead = self::lead(['contact_id' => $contact->id, 'trip_id' => $trip->id, 'status' => LeadStatus::Quoted]);

        $quote = Quote::create(array_merge([
            'lead_id' => $lead->id,
            'contact_id' => $contact->id,
            'trip_id' => $trip->id,
            'trip_departure_id' => $departure->id,
            'title' => 'Everest Base Camp for two',
            'status' => QuoteStatus::Sent,
            'start_date' => $departure->start_date,
            'end_date' => $departure->end_date,
            'pax' => 2,
            'currency' => 'USD',
            'deposit_percent' => 20,
            'valid_until' => now()->addDays(14),
            'sent_at' => now(),
        ], $attributes));

        $quote->items()->create(['description' => 'EBC trek, per person', 'quantity' => 2, 'unit_price' => 1500]);
        $quote->recalculate();
        $quote->save();

        return $quote->refresh();
    }

    public static function booking(array $attributes = [], ?TripDeparture $departure = null): Booking
    {
        $trip = $departure?->trip ?? self::trip();
        $departure ??= self::departure($trip);
        $contact = self::contact();
        $lead = self::lead(['contact_id' => $contact->id, 'trip_id' => $trip->id]);

        return Booking::create(array_merge([
            'lead_id' => $lead->id,
            'contact_id' => $contact->id,
            'trip_id' => $trip->id,
            'trip_departure_id' => $departure->id,
            'status' => BookingStatus::PendingDeposit,
            'start_date' => $departure->start_date,
            'pax' => 2,
            'currency' => 'USD',
            'total' => 3000,
            'deposit_amount' => 600,
            'amount_paid' => 0,
        ], $attributes));
    }

    public static function payment(Booking $booking, array $attributes = []): Payment
    {
        return $booking->payments()->create(array_merge([
            'contact_id' => $booking->contact_id,
            'type' => PaymentType::Deposit,
            'description' => 'Deposit',
            'amount' => 600,
            'currency' => 'USD',
            'status' => PaymentStatus::Pending,
            'expires_at' => now()->addDays(14),
        ], $attributes));
    }
}
