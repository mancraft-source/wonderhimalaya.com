<?php

use App\Http\Controllers\Api\V1\ActivityController;
use App\Http\Controllers\Api\V1\BookingController;
use App\Http\Controllers\Api\V1\ContactController;
use App\Http\Controllers\Api\V1\DepartureController;
use App\Http\Controllers\Api\V1\DestinationController;
use App\Http\Controllers\Api\V1\EnquiryController;
use App\Http\Controllers\Api\V1\LeadController;
use App\Http\Controllers\Api\V1\MeController;
use App\Http\Controllers\Api\V1\PageController;
use App\Http\Controllers\Api\V1\PostController;
use App\Http\Controllers\Api\V1\QuoteController;
use App\Http\Controllers\Api\V1\SearchController;
use App\Http\Controllers\Api\V1\SettingsController;
use App\Http\Controllers\Api\V1\TeamController;
use App\Http\Controllers\Api\V1\TestimonialController;
use App\Http\Controllers\Api\V1\TripController;
use Illuminate\Support\Facades\Route;

/*
| Public, cacheable read endpoints (published content only).
*/
Route::middleware('cache.headers:public;max_age=300;etag')->group(function () {
    Route::get('trips', [TripController::class, 'index'])->name('trips.index');
    Route::get('trips/{slug}', [TripController::class, 'show'])->name('trips.show');
    Route::get('trips/{slug}/departures', [TripController::class, 'departures'])->name('trips.departures');
    Route::get('departures', [DepartureController::class, 'index'])->name('departures.index');
    Route::get('destinations', [DestinationController::class, 'index'])->name('destinations.index');
    Route::get('destinations/{slug}', [DestinationController::class, 'show'])->name('destinations.show');
    Route::get('activities', [ActivityController::class, 'index'])->name('activities.index');
    Route::get('activities/{slug}', [ActivityController::class, 'show'])->name('activities.show');
    Route::get('posts', [PostController::class, 'index'])->name('posts.index');
    Route::get('posts/{slug}', [PostController::class, 'show'])->name('posts.show');
    Route::get('pages/{path}', [PageController::class, 'show'])->name('pages.show')->where('path', '.*');
    Route::get('testimonials', [TestimonialController::class, 'index'])->name('testimonials.index');
    Route::get('team', [TeamController::class, 'index'])->name('team.index');
    Route::get('search', SearchController::class)->name('search');
    Route::get('settings/public', SettingsController::class)->name('settings.public');
});

/*
| Public write: enquiries (rate limited; Turnstile when configured).
*/
Route::post('enquiries', [EnquiryController::class, 'store'])->middleware('throttle:enquiries')->name('enquiries.store');

/*
| Authenticated (Sanctum personal access tokens). Abilities gate the token; policies gate the user.
| `read` is an umbrella read ability; writes always need the specific *:write ability.
*/
Route::middleware(['auth:sanctum', 'api.active'])->group(function () {
    Route::get('me', MeController::class)->name('me');

    Route::middleware('ability:read,leads:read,leads:write')->group(function () {
        Route::get('leads', [LeadController::class, 'index'])->name('leads.index');
        Route::get('leads/{lead}', [LeadController::class, 'show'])->name('leads.show');
        Route::get('contacts', [ContactController::class, 'index'])->name('contacts.index');
        Route::get('contacts/{contact}', [ContactController::class, 'show'])->name('contacts.show');
    });

    Route::middleware('abilities:leads:write')->group(function () {
        Route::post('leads', [LeadController::class, 'store'])->name('leads.store');
        Route::patch('leads/{lead}', [LeadController::class, 'update'])->name('leads.update');
        Route::post('leads/{lead}/notes', [LeadController::class, 'storeNote'])->name('leads.notes.store');
    });

    Route::middleware('ability:read,bookings:read,bookings:write')->group(function () {
        Route::get('bookings', [BookingController::class, 'index'])->name('bookings.index');
        Route::get('bookings/{number}', [BookingController::class, 'show'])->name('bookings.show');
        Route::get('quotes/{number}', [QuoteController::class, 'show'])->name('quotes.show');
    });

    Route::post('bookings/{number}/payment-links', [BookingController::class, 'storePaymentLink'])
        ->middleware('abilities:bookings:write')
        ->name('bookings.payment-links.store');
});
