<?php

use App\Http\Controllers\Site\ActivityController;
use App\Http\Controllers\Site\BlogController;
use App\Http\Controllers\Site\DepartureController;
use App\Http\Controllers\Site\DestinationController;
use App\Http\Controllers\Site\HomeController;
use App\Http\Controllers\Site\PageController;
use App\Http\Controllers\Site\SeoController;
use App\Http\Controllers\Site\StaticController;
use App\Http\Controllers\Site\TeamController;
use App\Http\Controllers\Site\TripController;
use Illuminate\Support\Facades\Route;

require __DIR__.'/payments.php';

Route::get('/', HomeController::class)->name('home');

Route::get('/trips', [TripController::class, 'index'])->name('trips.index');
Route::get('/trip/{slug}', [TripController::class, 'show'])->name('trips.show');
Route::get('/trip/{slug}/itinerary.pdf', [TripController::class, 'pdf'])->name('trips.pdf')->middleware('throttle:20,1');
Route::get('/destination/{slug}', [DestinationController::class, 'show'])->name('destinations.show')->where('slug', '[a-z0-9-]+(/[a-z0-9-]+)?');
Route::get('/activities/{slug}', [ActivityController::class, 'show'])->name('activities.show');
Route::get('/fixed-departures', [DepartureController::class, 'index'])->name('departures');

Route::get('/tailor-made', [StaticController::class, 'tailorMade'])->name('tailor-made');
Route::get('/b2b', [StaticController::class, 'b2b'])->name('b2b');
Route::get('/contact', [StaticController::class, 'contact'])->name('contact');
Route::get('/thank-you', [StaticController::class, 'thanks'])->name('thanks');
Route::get('/reviews', [StaticController::class, 'reviews'])->name('reviews');
Route::get('/search', [StaticController::class, 'search'])->name('search');

Route::get('/our-team', [TeamController::class, 'index'])->name('team.index');
Route::get('/our-team/{slug}', [TeamController::class, 'show'])->name('team.show');

Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/category/{slug}', [BlogController::class, 'category'])->name('blog.category');
Route::get('/blog/{slug}', [BlogController::class, 'show'])->name('blog.show');

Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');

// CMS pages last: /about-us, /travel-guide/nepal-visa, ...
Route::get('/{path}', [PageController::class, 'show'])->name('pages.show')->where('path', '(?!(?:admin|api|livewire|storage|docs|up)(?:/|$))[a-z0-9-]+(?:/[a-z0-9-]+)*');
