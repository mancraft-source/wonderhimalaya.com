<?php

namespace App\Livewire;

use App\Models\Testimonial;
use App\Models\Trip;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Spatie\Honeypot\Http\Livewire\Concerns\HoneypotData;
use Spatie\Honeypot\Http\Livewire\Concerns\UsesSpamProtection;

/** Traveller review submission for a trip; saved unpublished until staff approve it in the admin. */
class TripReviewForm extends Component
{
    use UsesSpamProtection;

    #[Locked]
    public int $trip_id;

    public HoneypotData $extraFields;

    public bool $open = false;

    public int $rating = 5;

    public string $title = '';

    public string $body = '';

    public string $author_name = '';

    public string $author_email = '';

    public string $author_country = '';

    public ?string $travelled_on = null;

    public bool $consent = false;

    public bool $submitted = false;

    public function mount(Trip $trip): void
    {
        $this->trip_id = $trip->id;
        $this->extraFields = new HoneypotData;
    }

    public function submit(): void
    {
        $this->protectAgainstSpam();

        $data = $this->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'title' => ['nullable', 'string', 'max:120'],
            'body' => ['required', 'string', 'min:30', 'max:3000'],
            'author_name' => ['required', 'string', 'max:80'],
            'author_email' => ['required', 'email:rfc,strict', 'max:190'],
            'author_country' => ['nullable', 'string', 'max:80'],
            'travelled_on' => ['nullable', 'date_format:Y-m', 'before_or_equal:'.now()->format('Y-m')],
            'consent' => ['accepted'],
        ], ['consent.accepted' => 'Please confirm you travelled with us and agree to your review being published.']);

        $key = 'review:'.request()->ip();
        if (RateLimiter::tooManyAttempts($key, 3)) {
            $this->addError('body', 'Thank you — you have already sent several reviews today.');

            return;
        }
        RateLimiter::hit($key, 86400);

        Testimonial::create([
            'trip_id' => $this->trip_id,
            'rating' => $data['rating'],
            'title' => $data['title'] ?: null,
            'body' => strip_tags($data['body']),
            'author_name' => $data['author_name'],
            'author_email' => strtolower($data['author_email']),
            'author_country' => $data['author_country'] ?: null,
            'travelled_on' => $data['travelled_on'] ? $data['travelled_on'].'-01' : null,
            'reviewed_at' => today(),
            'source' => 'website',
            'is_published' => false,
            'is_featured' => false,
        ]);

        $this->submitted = true;
    }

    public function render()
    {
        return view('livewire.trip-review-form');
    }
}
