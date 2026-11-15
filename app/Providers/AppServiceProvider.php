<?php

namespace App\Providers;

use App\Models\Activity;
use App\Models\Booking;
use App\Models\Company;
use App\Models\Contact;
use App\Models\CrmTask;
use App\Models\Destination;
use App\Models\Lead;
use App\Models\Note;
use App\Models\Page;
use App\Models\Payment;
use App\Models\Post;
use App\Models\Quote;
use App\Models\TeamMember;
use App\Models\Trip;
use App\Models\User;
use App\Policies\ActivityLogPolicy;
use App\Policies\RolePolicy;
use App\Support\Permissions;
use App\View\Composers\SiteComposer;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Spatie\Activitylog\Models\Activity as ActivityLogEntry;
use Spatie\Permission\Models\Role;

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
            'trip' => Trip::class,
            'destination' => Destination::class,
            'activity' => Activity::class,
            'page' => Page::class,
            'post' => Post::class,
            'team_member' => TeamMember::class,
            'contact' => Contact::class,
            'company' => Company::class,
            'lead' => Lead::class,
            'quote' => Quote::class,
            'booking' => Booking::class,
            'payment' => Payment::class,
            'crm_task' => CrmTask::class,
            'note' => Note::class,
            'role' => Role::class,
        ]);

        Gate::before(fn (User $user, string $ability, array $arguments) => $user->hasRole(Permissions::ADMIN_ROLE) && ! self::isGuardedForAdmins($ability, $arguments) ? true : null);
        Gate::policy(ActivityLogEntry::class, ActivityLogPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);

        Password::defaults(fn () => $this->app->isProduction()
            ? Password::min(12)->mixedCase()->numbers()->symbols()->uncompromised()
            : Password::min(8));

        if ($this->app->isProduction()) {
            URL::forceHttps();
        }

        $this->configureRateLimiting();

        View::composer(['components.layouts.site', 'site.*', 'errors.*', 'errors::*'], SiteComposer::class);
    }

    /**
     * Admins pass every check except the safety rails their policies enforce:
     * the locked admin role, roles still in use and the last active admin.
     *
     * @param  array<int, mixed>  $arguments
     */
    private static function isGuardedForAdmins(string $ability, array $arguments): bool
    {
        $subject = $arguments[0] ?? null;

        return in_array($ability, ['update', 'delete', 'forceDelete'], true) && $subject instanceof Role
            || in_array($ability, ['delete', 'forceDelete'], true) && $subject instanceof User;
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
