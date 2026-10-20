<?php

namespace App\Livewire;

use App\Enums\LeadType;
use App\Livewire\Concerns\SubmitsEnquiries;
use Livewire\Component;

class ContactForm extends Component
{
    use SubmitsEnquiries;

    public string $first_name = '';

    public string $last_name = '';

    public string $email = '';

    public string $phone = '';

    public string $country = '';

    public string $message = '';

    public bool $privacy_accepted = false;

    public bool $marketing_consent = false;

    public ?string $reference = null;

    public function submit(): void
    {
        $rules = $this->enquiryRules(['first_name', 'last_name', 'email', 'phone', 'country', 'message', 'privacy_accepted', 'marketing_consent']);
        $rules['message'] = ['required', 'string', 'min:10', 'max:5000'];

        $data = $this->validate($rules, ['privacy_accepted.accepted' => 'Please accept the privacy policy so we can reply to you.']);

        $this->reference = $this->sendEnquiry($data + ['type' => LeadType::Contact->value])->reference;
        $this->dispatch('enquiry-sent', type: 'contact');
    }

    public function render()
    {
        return view('livewire.contact-form');
    }
}
