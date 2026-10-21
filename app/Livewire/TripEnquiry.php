<?php

namespace App\Livewire;

use App\Enums\LeadType;
use App\Livewire\Concerns\SubmitsEnquiries;
use App\Models\Trip;
use App\Models\TripDeparture;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

/** Booking/enquiry panel on a trip page: join a group departure, or request a private trip. */
class TripEnquiry extends Component
{
    use SubmitsEnquiries;

    #[Locked]
    public int $trip_id;

    public string $mode = 'private';

    public ?int $trip_departure_id = null;

    public ?int $tier_id = null;

    public ?string $start_date = null;

    public int $adults = 2;

    public int $children = 0;

    public string $first_name = '';

    public string $last_name = '';

    public string $email = '';

    public string $phone = '';

    public string $country = '';

    public string $message = '';

    public string $heard_about = '';

    public const HEARD_ABOUT = ['Google search', 'Tripadvisor', 'Instagram', 'Facebook', 'TikTok', 'YouTube', 'Friend or family', 'Returning traveller', 'Travel blog / article', 'Other'];

    public bool $privacy_accepted = false;

    public bool $marketing_consent = false;

    public ?string $reference = null;

    public function mount(Trip $trip, ?int $departure = null): void
    {
        $this->trip_id = $trip->id;
        $this->mode = $trip->upcomingDepartures()->exists() ? 'group' : 'private';
        $this->trip_departure_id = $departure;
        if ($departure) {
            $this->mode = 'group';
        }
    }

    #[Computed]
    public function trip(): Trip
    {
        return Trip::with('tiers')->findOrFail($this->trip_id);
    }

    #[Computed]
    public function departures(): Collection
    {
        return TripDeparture::where('trip_id', $this->trip_id)
            ->whereDate('start_date', '>=', today())
            ->orderBy('start_date')
            ->get()
            ->filter(fn (TripDeparture $d) => $d->isBookable());
    }

    #[On('pick-departure')]
    public function selectDeparture(int $id): void
    {
        $this->mode = $id > 0 ? 'group' : 'private';
        $this->trip_departure_id = $id > 0 ? $id : null;
        $this->dispatch('scroll-to-enquiry');
    }

    public function submit(): void
    {
        $rules = $this->enquiryRules(['first_name', 'last_name', 'email', 'phone', 'country', 'tier_id', 'start_date', 'adults', 'children', 'message', 'privacy_accepted', 'marketing_consent']);
        $rules['adults'] = ['required', 'integer', 'min:1', 'max:40'];

        if ($this->mode === 'group') {
            $rules['trip_departure_id'] = ['required', 'integer', 'exists:trip_departures,id'];
        } else {
            $rules['start_date'] = ['nullable', 'date', 'after_or_equal:today'];
        }

        $data = $this->validate($rules, [
            'trip_departure_id.required' => 'Please choose a departure date.',
            'privacy_accepted.accepted' => 'Please accept the privacy policy so we can reply to you.',
        ]);

        if ($this->mode === 'group' && TripDeparture::find($this->trip_departure_id)?->trip_id !== $this->trip_id) {
            $this->addError('trip_departure_id', 'Please choose a departure date.');

            return;
        }

        if ($this->heard_about !== '' && in_array($this->heard_about, self::HEARD_ABOUT, true)) {
            $data['message'] = trim(($data['message'] ?? '')."\n\nFound us via: ".$this->heard_about);
        }

        $lead = $this->sendEnquiry($data + [
            'type' => ($this->mode === 'group' ? LeadType::Departure : LeadType::PrivateTrip)->value,
            'trip_id' => $this->trip_id,
            'trip_departure_id' => $this->mode === 'group' ? $this->trip_departure_id : null,
        ]);

        $this->reference = $lead->reference;
        $this->dispatch('enquiry-sent', type: $this->mode, trip: $this->trip->title);
    }

    public function render()
    {
        return view('livewire.trip-enquiry');
    }
}
