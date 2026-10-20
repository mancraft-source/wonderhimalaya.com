<?php

namespace App\Livewire;

use App\Enums\CompanyType;
use App\Enums\LeadType;
use App\Livewire\Concerns\SubmitsEnquiries;
use Illuminate\Validation\Rule;
use Livewire\Component;

/** B2B partnership enquiry → company + contact + "b2b" lead. */
class PartnerForm extends Component
{
    use SubmitsEnquiries;

    public string $company_name = '';

    public string $company_website = '';

    public string $company_type = 'agency';

    public array $interests = [];

    public string $first_name = '';

    public string $last_name = '';

    public string $email = '';

    public string $phone = '';

    public string $country = '';

    public string $message = '';

    public bool $privacy_accepted = false;

    public ?string $reference = null;

    public const SERVICES = ['White-label ground handling', 'FIT itinerary curation', 'Small-group expeditions', 'Peak climbing & expeditions', 'MICE & corporate', 'Education & volunteer groups'];

    public function submit(): void
    {
        $rules = $this->enquiryRules(['company_name', 'company_website', 'first_name', 'last_name', 'email', 'phone', 'country', 'message', 'privacy_accepted', 'interests', 'interests.*']);
        $rules['company_name'] = ['required', 'string', 'max:160'];
        $rules['company_type'] = ['required', Rule::enum(CompanyType::class)];
        $rules['country'] = ['required', 'string', 'max:80'];

        $data = $this->validate($rules, ['privacy_accepted.accepted' => 'Please accept the privacy policy so we can reply to you.']);

        $this->reference = $this->sendEnquiry($data + ['type' => LeadType::B2B->value, 'company_website' => $this->company_website ?: null])->reference;
        $this->dispatch('enquiry-sent', type: 'b2b');
    }

    public function render()
    {
        return view('livewire.partner-form');
    }
}
