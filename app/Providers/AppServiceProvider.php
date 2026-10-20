<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Model::shouldBeStrict(! $this->app->isProduction());
        Model::automaticallyEagerLoadRelationships();

        Relation::enforceMorphMap([
            'user' => User::class,
            'trip' => \App\Models\Trip::class,
            'destination' => \App\Models\Destination::class,
            'activity' => \App\Models\Activity::class,
            'page' => \App\Models\Page::class,
            'post' => \App\Models\Post::class,
            'team_member' => \App\Models\TeamMember::class,
            'contact' => \App\Models\Contact::class,
            'company' => \App\Models\Company::class,
            'lead' => \App\Models\Lead::class,
            'quote' => \App\Models\Quote::class,
            'booking' => \App\Models\Booking::class,
            'payment' => \App\Models\Payment::class,
            'crm_task' => \App\Models\CrmTask::class,
            'note' => \App\Models\Note::class,
        ]);

        Gate::before(fn (User $user) => $user->hasRole('admin') ? true : null);

        Password::defaults(fn () => $this->app->isProduction()
            ? Password::min(12)->mixedCase()->numbers()->symbols()->uncompromised()
            : Password::min(8));

        if ($this->app->isProduction()) {
            URL::forceHttps();
        }

        $this->configureRateLimiting();
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('api', fn (Request $request) => $request->user()
            ? Limit::perMinute(120)->by('user:'.$request->user()->id)
            : Limit::perMinute(60)->by('ip:'.$request->ip()));

        RateLimiter::for('enquiries', fn (Request $request) => [
            Limit::perMinute(3)->by('enquiry-min:'.$request->ip()),
            Limit::perDay(20)->by('enquiry-day:'.$request->ip()),
        ]);

        RateLimiter::for('webhooks', fn (Request $request) => Limit::perMinute(300)->by($request->ip()));
    }
}
