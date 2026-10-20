<?php

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Enums\QuoteStatus;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Setting;
use Tests\Support\Fixtures;

it('marks a quote viewed on first public view but not for staff', function () {
    $quote = Fixtures::quote();

    $this->actingAs(Fixtures::user('sales'))->get(route('quotes.show', $quote->uuid))->assertOk();
    expect($quote->fresh()->status)->toBe(QuoteStatus::Sent);

    auth()->logout();

    $this->get(route('quotes.show', $quote->uuid))
        ->assertOk()
        ->assertSee($quote->title)
        ->assertSee('Accept &amp; pay deposit', false)
        ->assertSee('noindex', false);

    expect($quote->fresh()->status)->toBe(QuoteStatus::Viewed)
        ->and($quote->fresh()->viewed_at)->not->toBeNull();
});

it('hides drafts and shows expired quotes without the accept button', function () {
    $draft = Fixtures::quote(['status' => QuoteStatus::Draft]);
    $this->get(route('quotes.show', $draft->uuid))->assertNotFound();

    $expired = Fixtures::quote(['valid_until' => now()->subDays(2)]);
    $this->get(route('quotes.show', $expired->uuid))->assertOk()->assertSee('This quote has expired')->assertDontSee('Accept &amp; pay deposit', false);
    $this->post(route('quotes.accept', $expired->uuid), ['accept_terms' => '1'])->assertRedirect(route('quotes.show', $expired->uuid));
    expect(Booking::count())->toBe(0);
});

it('accepts a quote, creates the booking and a pending deposit payment, then redirects to pay', function () {
    Setting::put('booking', ['balance_due_days' => 45]);
    $quote = Fixtures::quote();

    $this->post(route('quotes.accept', $quote->uuid), [])->assertSessionHasErrors('accept_terms');

    $response = $this->post(route('quotes.accept', $quote->uuid), ['accept_terms' => '1']);

    $booking = Booking::firstOrFail();
    $payment = Payment::firstOrFail();

    $response->assertRedirect(route('pay.show', $payment));

    expect($quote->fresh()->status)->toBe(QuoteStatus::Accepted)
        ->and($booking->status)->toBe(BookingStatus::PendingDeposit)
        ->and($booking->quote_id)->toBe($quote->id)
        ->and($booking->pax)->toBe(2)
        ->and((float) $booking->total)->toBe(3000.0)
        ->and((float) $booking->deposit_amount)->toBe(600.0)
        ->and($booking->balance_due_on->toDateString())->toBe($booking->start_date->copy()->subDays(45)->toDateString())
        ->and($booking->travellers()->where('is_lead', true)->count())->toBe(1)
        ->and($payment->payable->is($booking))->toBeTrue()
        ->and($payment->status)->toBe(PaymentStatus::Pending)
        ->and($payment->type)->toBe(PaymentType::Deposit)
        ->and((float) $payment->amount)->toBe(600.0)
        ->and($payment->expires_at->isFuture())->toBeTrue();

    // Double submit reuses the same booking + link.
    $this->post(route('quotes.accept', $quote->uuid), ['accept_terms' => '1'])->assertRedirect(route('pay.show', $payment));
    expect(Booking::count())->toBe(1)->and(Payment::count())->toBe(1);

    $this->get(route('pay.show', $payment))->assertOk()->assertSee('Bank transfer')->assertSee($booking->number);
});

it('declines a quote and notes it on the lead', function () {
    $quote = Fixtures::quote();

    $this->post(route('quotes.decline', $quote->uuid), ['reason' => 'Going to Peru instead'])->assertRedirect();

    expect($quote->fresh()->status)->toBe(QuoteStatus::Declined)
        ->and($quote->lead->notes()->first()->body)->toContain('Going to Peru instead');
});

it('renders the quote PDF', function () {
    $quote = Fixtures::quote();

    $response = $this->get(route('quotes.pdf', $quote->uuid))->assertOk();
    expect($response->headers->get('content-type'))->toBe('application/pdf');
});

it('does not offer expired payment links', function () {
    $payment = Fixtures::payment(Fixtures::booking(), ['expires_at' => now()->subMinute()]);

    expect($payment->isPayable())->toBeFalse();

    $this->get(route('pay.show', $payment))->assertOk()->assertSee('This payment link has expired')->assertDontSee('Choose how to pay');
    $this->post(route('pay.start', [$payment, 'bank_transfer']))->assertRedirect(route('pay.show', $payment));
});

it('shows bank transfer instructions without marking the payment paid', function () {
    Setting::put('bank_transfer', "Nabil Bank\nSWIFT NARBNPKA");
    $payment = Fixtures::payment(Fixtures::booking());

    $this->post(route('pay.start', [$payment, 'bank_transfer']))->assertOk()->assertSee('SWIFT NARBNPKA');
    expect($payment->fresh()->status)->toBe(PaymentStatus::Pending);

    $this->post(route('pay.start', [$payment, 'card']))->assertNotFound(); // bank gateway not configured
});

it('lets travellers submit encrypted details', function () {
    $booking = Fixtures::booking(['status' => BookingStatus::Confirmed]);

    $this->get(route('bookings.travellers.edit', $booking->uuid))->assertOk()->assertSee('Traveller 2');

    $this->post(route('bookings.travellers.update', $booking->uuid), ['travellers' => [
        ['first_name' => 'Ana', 'last_name' => 'Silva', 'passport_number' => 'P1234567', 'passport_expiry' => now()->addYears(3)->toDateString()],
        ['first_name' => '', 'last_name' => ''],
    ]])->assertRedirect()->assertSessionHasNoErrors();

    $traveller = $booking->travellers()->first();
    expect($traveller->passport_number)->toBe('P1234567')
        ->and(DB::table('travellers')->value('passport_number'))->not->toContain('P1234567');

    $this->get(route('bookings.travellers.edit', $booking->uuid))->assertDontSee('P1234567');
});
