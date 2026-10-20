<?php

namespace App\Livewire;

use App\Enums\LeadType;
use App\Livewire\Concerns\SubmitsEnquiries;
use App\Models\Destination;
use App\Models\Tier;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

/** Five-step tailor-made trip builder (Sacred Nepal-style), creating a "tailor_made" lead. */
class TailorMadeBuilder extends Component
{
    use SubmitsEnquiries;

    public const STEPS = ['Where', 'Experience', 'When', 'Who', 'You'];

    public const INTERESTS = [
        'classic-trek' => ['Classic trekking', 'Iconic trails: Everest, Annapurna, Langtang'],
        'remote' => ['Remote & wild', 'Restricted valleys, few other trekkers'],
        'peak' => ['Peak climbing', 'Island, Mera, Lobuche and beyond'],
        'heli' => ['Helicopter & scenic flights', 'Mountains without the miles'],
        'culture' => ['Culture & heritage', 'Temples, festivals, living traditions'],
        'wildlife' => ['Jungle & wildlife', 'Chitwan & Bardiya safaris'],
        'spiritual' => ['Spiritual & wellness', 'Monasteries, meditation, yoga'],
        'adventure' => ['Soft adventure', 'Paragliding, rafting, biking'],
        'family' => ['Family travel', 'Gentle pace, kid-friendly lodges'],
        'luxury' => ['Luxury lodges', 'The finest stays, private guiding'],
        'photography' => ['Photography', 'Golden-hour timing, best viewpoints'],
        'honeymoon' => ['Honeymoon & celebrations', 'Moments made to remember'],
    ];

    public const BUDGETS = ['Under US$1,500', 'US$1,500 – 3,000', 'US$3,000 – 5,000', 'US$5,000 – 8,000', 'US$8,000+', 'Not sure yet'];

    public int $step = 1;

    #[Url(as: 'to', except: [])]
    public array $destinations = [];

    #[Url(as: 'style', except: [])]
    public array $interests = [];

    public ?string $travel_month = null;

    public ?string $start_date = null;

    public string $date_flexibility = 'Flexible by a week or two';

    public int $duration_days = 12;

    public int $adults = 2;

    public int $children = 0;

    public ?int $tier_id = null;

    public ?string $budget = null;

    public string $first_name = '';

    public string $last_name = '';

    public string $email = '';

    public string $phone = '';

    public string $country = '';

    public string $message = '';

    public bool $privacy_accepted = false;

    public bool $marketing_consent = false;

    public ?string $reference = null;

    #[Computed(persist: true)]
    public function places(): array
    {
        $regions = Destination::whereHas('parent', fn ($q) => $q->where('slug', 'nepal'))->withCount('trips')->orderByDesc('trips_count')->pluck('name')->all();
        $countries = Destination::whereNull('parent_id')->where('slug', '!=', 'nepal')->orderBy('sort')->pluck('name')->all();

        return array_values(array_unique(array_merge($regions, $countries, ['Not sure yet — inspire me'])));
    }

    #[Computed(persist: true)]
    public function tiers()
    {
        return Tier::orderBy('sort')->get();
    }

    /** @return array<string, string> */
    #[Computed]
    public function months(): array
    {
        return collect(range(0, 17))->mapWithKeys(function (int $i) {
            $m = Carbon::now()->startOfMonth()->addMonths($i);

            return [$m->format('Y-m') => $m->format('M Y')];
        })->all();
    }

    public function toggle(string $field, string $value): void
    {
        if (! in_array($field, ['destinations', 'interests'], true)) {
            return;
        }

        $list = $this->{$field};
        $this->{$field} = in_array($value, $list, true) ? array_values(array_diff($list, [$value])) : array_slice([...$list, $value], 0, 12);
    }

    public function next(): void
    {
        $this->validateStep($this->step);
        $this->step = min(count(self::STEPS), $this->step + 1);
    }

    public function back(): void
    {
        $this->step = max(1, $this->step - 1);
    }

    public function goTo(int $step): void
    {
        if ($step < $this->step) {
            $this->step = max(1, $step);
        }
    }

    private function validateStep(int $step): void
    {
        match ($step) {
            1 => $this->validate(['destinations' => ['required', 'array', 'min:1']], ['destinations.required' => 'Pick at least one place — or "inspire me".']),
            2 => $this->validate(['interests' => ['required', 'array', 'min:1']], ['interests.required' => 'Choose at least one travel style.']),
            3 => $this->validate($this->enquiryRules(['travel_month', 'start_date', 'date_flexibility', 'duration_days'])),
            4 => $this->validate(['adults' => ['required', 'integer', 'min:1', 'max:40']] + $this->enquiryRules(['children', 'tier_id', 'budget'])),
            default => null,
        };
    }

    public function submit(): void
    {
        foreach (range(1, 4) as $s) {
            try {
                $this->validateStep($s);
            } catch (\Illuminate\Validation\ValidationException $e) {
                $this->step = $s;
                throw $e;
            }
        }

        $data = $this->validate($this->enquiryRules(['first_name', 'last_name', 'email', 'phone', 'country', 'message', 'privacy_accepted', 'marketing_consent']), [
            'privacy_accepted.accepted' => 'Please accept the privacy policy so we can reply to you.',
        ]);

        $lead = $this->sendEnquiry($data + [
            'type' => LeadType::TailorMade->value,
            'destinations' => array_map(fn ($v) => substr((string) $v, 0, 80), $this->destinations),
            'interests' => collect($this->interests)->map(fn ($k) => self::INTERESTS[$k][0] ?? null)->filter()->values()->all(),
            'travel_month' => $this->travel_month ? Carbon::createFromFormat('Y-m', $this->travel_month)?->format('F Y') : null,
            'start_date' => $this->start_date ?: null,
            'date_flexibility' => $this->date_flexibility,
            'duration_days' => $this->duration_days,
            'adults' => $this->adults,
            'children' => $this->children,
            'tier_id' => $this->tier_id,
            'budget' => $this->budget,
        ]);

        $this->reference = $lead->reference;
        $this->dispatch('enquiry-sent', type: 'tailor_made');
    }

    public function render()
    {
        return view('livewire.tailor-made-builder');
    }
}
