<?php

// Public quote / payment / webhook routes (loaded from routes/web.php, so `web` middleware applies).
// Access is by unguessable UUID + rate limiting; amounts always come from the Payment record.

use App\Http\Controllers\Payments\PaymentController;
use App\Http\Controllers\Payments\PaymentWebhookController;
use App\Http\Controllers\Payments\QuoteController;
use App\Http\Controllers\Payments\TravellerController;
use Illuminate\Support\Facades\Route;

$uuid = '[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}';

Route::middleware('throttle:payments')->group(function () use ($uuid) {
    Route::prefix('quote/{uuid}')->name('quotes.')->where(['uuid' => $uuid])->controller(QuoteController::class)->group(function () {
        Route::get('/', 'show')->name('show');
        Route::get('pdf', 'pdf')->name('pdf');
        Route::post('accept', 'accept')->name('accept');
        Route::post('decline', 'decline')->name('decline');
    });

    Route::prefix('pay/{payment}')->name('pay.')->where(['payment' => $uuid])->controller(PaymentController::class)->group(function () {
        Route::get('/', 'show')->name('show');
        Route::get('cancel', 'cancel')->name('cancel');
        Route::get('success', 'success')->name('success');
        Route::post('{gateway}', 'start')->name('start')->where('gateway', '[a-z_]+');
        // GET for redirect gateways (PayPal); POST for hosted-form bank gateways (CSRF-exempt, signature verified).
        Route::match(['get', 'post'], '{gateway}/return', 'return')->name('return')->where('gateway', '[a-z_]+');
    });

    Route::prefix('booking/{uuid}/travellers')->name('bookings.travellers.')->where(['uuid' => $uuid])->controller(TravellerController::class)->group(function () {
        Route::get('/', 'edit')->name('edit');
        Route::post('/', 'update')->name('update');
    });
});

Route::post('webhooks/payments/{gateway}', PaymentWebhookController::class)
    ->middleware('throttle:webhooks')
    ->where('gateway', '[a-z_]+')
    ->name('webhooks.payments');
