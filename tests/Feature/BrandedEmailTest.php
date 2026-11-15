<?php

use App\Models\Setting;
use App\Notifications\EnquiryReceived;
use Database\Factories\ContactFactory;
use Database\Factories\LeadFactory;
use Illuminate\Notifications\AnonymousNotifiable;

beforeEach(function () {
    config(['app.url' => 'https://wonderhimalaya.com']);
});

function renderEnquiryEmail(): string
{
    $lead = LeadFactory::new()->create(['contact_id' => ContactFactory::new()->create(['first_name' => 'Ana'])->id]);
    $notifiable = (new AnonymousNotifiable)->route('mail', 'ana@example.com');

    return (string) (new EnquiryReceived($lead))->toMail($notifiable)->render();
}

it('renders customer emails with the Wonder Himalaya theme, header and footer', function () {
    Setting::put('company', [
        'name' => 'Wonder Himalaya',
        'legal_name' => 'Wonder Himalaya Treks & Expedition',
        'address' => 'Thamel, Kathmandu, Nepal',
        'phone' => '+977-01-5928066',
        'email' => 'info@wonderhimalaya.com',
    ]);
    Setting::put('social', ['facebook' => 'https://www.facebook.com/wonderhimalayanepal', 'instagram' => '', 'youtube' => null]);

    $html = renderEnquiryEmail();

    expect($html)
        ->toContain('src="https://wonderhimalaya.com/images/brand/logo-badge-160.png"')
        ->toContain('href="https://wonderhimalaya.com"')
        ->toContain('background-color: #f4efe6')
        ->toContain('background-color: #082047')
        ->toContain("font-family: 'Georgia'")
        ->toContain('Namaste Ana,')
        ->toContain('Thamel, Kathmandu, Nepal')
        ->toContain('href="tel:+977015928066"')
        ->toContain('mailto:info@wonderhimalaya.com')
        ->toContain('Wonder Himalaya Treks &amp; Expedition')
        ->toContain('href="https://www.facebook.com/wonderhimalayanepal"')
        ->toContain('class="preheader"')
        ->not->toContain('Instagram')
        ->not->toContain('YouTube')
        ->not->toContain('laravel.com');
});

it('falls back to the published business details when settings are empty', function () {
    $html = renderEnquiryEmail();

    expect($html)
        ->toContain('Wonder Himalaya')
        ->toContain('info@wonderhimalaya.com')
        ->toContain('+977-01-5928066')
        ->not->toContain('Facebook');
});
