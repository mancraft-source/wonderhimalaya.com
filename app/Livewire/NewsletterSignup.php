<?php

namespace App\Livewire;

use App\Models\Contact;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Spatie\Honeypot\Http\Livewire\Concerns\HoneypotData;
use Spatie\Honeypot\Http\Livewire\Concerns\UsesSpamProtection;

class NewsletterSignup extends Component
{
    use UsesSpamProtection;

    #[Validate('required|email:rfc,strict|max:190')]
    public string $email = '';

    public HoneypotData $extraFields;

    public bool $done = false;

    public function mount(): void
    {
        $this->extraFields = new HoneypotData;
    }

    public function subscribe(): void
    {
        $this->protectAgainstSpam();
        $this->validate();

        if (RateLimiter::tooManyAttempts('newsletter:'.request()->ip(), 5)) {
            $this->addError('email', 'Too many attempts — please try again later.');

            return;
        }
        RateLimiter::hit('newsletter:'.request()->ip(), 3600);

        $contact = Contact::firstOrNew(['email' => strtolower(trim($this->email))]);
        $contact->first_name ??= str($this->email)->before('@')->headline()->limit(60, '')->toString();
        $contact->source ??= 'newsletter';
        if (! $contact->marketing_consent) {
            $contact->marketing_consent = true;
            $contact->consent_at = now();
        }
        $contact->save();

        $this->reset('email');
        $this->done = true;
    }

    public function render()
    {
        return view('livewire.newsletter-signup');
    }
}
