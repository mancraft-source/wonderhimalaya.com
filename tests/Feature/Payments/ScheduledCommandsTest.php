<?php

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Enums\QuoteStatus;
use App\Models\Payment;
use App\Notifications\BalancePaymentDue;
use Illuminate\Support\Facades\Notification;
use Tests\Support\Fixtures;

it('sends balance reminders idempotently per day and reuses the link', function () {
    Notification::fake();

    $due = Fixtures::booking(['status' => BookingStatus::Confirmed, 'amount_paid' => 600, 'balance_due_on' => now()->addDays(5)]);
    Fixtures::booking(['status' => BookingStatus::Confirmed, 'amount_paid' => 600, 'balance_due_on' => now()->addDays(30)]);
    Fixtures::booking(['status' => BookingStatus::PaidInFull, 'amount_paid' => 3000, 'balance_due_on' => now()->addDays(2)]);

    $this->artisan('bookings:send-balance-reminders')->assertSuccessful();
    $this->artisan('bookings:send-balance-reminders')->assertSuccessful();

    Notification::assertSentOnDemandTimes(BalancePaymentDue::class, 1);

    $links = Payment::where('type', PaymentType::Balance)->get();
    expect($links)->toHaveCount(1)
        ->and($links->first()->payable->is($due))->toBeTrue()
        ->and((float) $links->first()->amount)->toBe(2400.0)
        ->and($links->first()->status)->toBe(PaymentStatus::Pending);

    $this->travel(1)->days();
    $this->artisan('bookings:send-balance-reminders')->assertSuccessful();

    Notification::assertSentOnDemandTimes(BalancePaymentDue::class, 2);
    expect(Payment::where('type', PaymentType::Balance)->count())->toBe(1);
});

it('expires sent and viewed quotes past their validity', function () {
    $old = Fixtures::quote(['valid_until' => now()->subDays(1)]);
    $viewed = Fixtures::quote(['valid_until' => now()->subDays(3), 'status' => QuoteStatus::Viewed]);
    $current = Fixtures::quote(['valid_until' => now()->addDay()]);
    $accepted = Fixtures::quote(['valid_until' => now()->subDays(3), 'status' => QuoteStatus::Accepted]);

    $this->artisan('quotes:expire')->assertSuccessful();

    expect($old->fresh()->status)->toBe(QuoteStatus::Expired)
        ->and($viewed->fresh()->status)->toBe(QuoteStatus::Expired)
        ->and($current->fresh()->status)->toBe(QuoteStatus::Sent)
        ->and($accepted->fresh()->status)->toBe(QuoteStatus::Accepted);
});
