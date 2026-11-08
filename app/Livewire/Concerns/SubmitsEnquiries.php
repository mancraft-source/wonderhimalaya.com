<?php

namespace App\Livewire\Concerns;

use App\Actions\Leads\SubmitEnquiry;
use App\Http\Requests\EnquiryRules;
use App\Models\Lead;
use App\Support\Turnstile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Spatie\Honeypot\Http\Livewire\Concerns\HoneypotData;
use Spatie\Honeypot\Http\Livewire\Concerns\UsesSpamProtection;

/**
 * Shared plumbing for public enquiry forms: honeypot, per-IP throttling,
 * optional Cloudflare Turnstile, first-touch attribution and the SubmitEnquiry action.
 * Components expose public properties named exactly like EnquiryRules keys.
 */
trait SubmitsEnquiries
{
    use UsesSpamProtection;

    public HoneypotData $extraFields;

    public ?string $turnstileToken = null;

    /** The page the form sits on, captured on first render (form posts go to /livewire/update, so the request URL can't tell). */
    #[Locked]
    public ?string $formPage = null;

    public function mountSubmitsEnquiries(): void
    {
        $this->extraFields = new HoneypotData;
        $this->formPage = substr(request()->fullUrl(), 0, 1000);
    }

    /** @param  array<int, string>  $fields */
    protected function enquiryRules(array $fields): array
    {
        return Arr::only(EnquiryRules::rules(), $fields);
    }

    /** @param  array<string, mixed>  $data  already validated by the component */
    protected function sendEnquiry(array $data): Lead
    {
        $this->protectAgainstSpam();

        $key = 'enquiry:'.request()->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'email' => 'Too many enquiries from this connection. Please try again in '.ceil(RateLimiter::availableIn($key) / 60).' minutes or email us directly.',
            ]);
        }
        RateLimiter::hit($key, 600);

        $this->verifyTurnstile();

        $session = session('attribution', []);

        return app(SubmitEnquiry::class)->handle($data + [
            'utm' => $session['utm'] ?? null,
            'landing_page' => $session['landing_page'] ?? null,
            'form_page' => $this->formPage,
            'source' => isset($session['utm']['gclid']) ? 'google_ads' : ($session['utm']['utm_source'] ?? 'website'),
        ], [
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'referrer' => $session['referrer'] ?? null,
        ]);
    }

    private function verifyTurnstile(): void
    {
        if (! config('services.turnstile.secret') || ! class_exists(Turnstile::class)) {
            return;
        }

        if (! Turnstile::verify($this->turnstileToken, request()->ip())) {
            throw ValidationException::withMessages(['turnstileToken' => 'Please complete the security check.']);
        }
    }
}
