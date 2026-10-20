<?php

namespace App\Livewire;

use App\Models\Activity;
use App\Models\Destination;
use App\Models\Difficulty;
use App\Models\Tier;
use App\Models\Trip;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/** Filterable trip listing; filters are reflected in the URL for sharing & SEO-friendly links. */
class TripFinder extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $q = '';

    #[Url(except: '')]
    public string $destination = '';

    #[Url(except: '')]
    public string $activity = '';

    #[Url(except: '')]
    public string $difficulty = '';

    #[Url(except: '')]
    public string $tier = '';

    #[Url(except: '')]
    public string $duration = '';

    #[Url(except: false)]
    public bool $fixed = false;

    #[Url(except: 'recommended')]
    public string $sort = 'recommended';

    public const DURATIONS = ['1-5' => 'Up to 5 days', '6-10' => '6–10 days', '11-15' => '11–15 days', '16-99' => '16+ days'];

    public function updating(string $name): void
    {
        if ($name !== 'page') {
            $this->resetPage();
        }
    }

    public function clear(): void
    {
        $this->reset(['q', 'destination', 'activity', 'difficulty', 'tier', 'duration', 'fixed', 'sort']);
        $this->resetPage();
    }

    #[Computed(persist: true, seconds: 600)]
    public function options(): array
    {
        return [
            'destinations' => Destination::with('children')->whereNull('parent_id')->orderBy('sort')->get()
                ->flatMap(fn ($c) => collect([['slug' => $c->slug, 'name' => $c->name, 'child' => false]])
                    ->merge($c->children->map(fn ($r) => ['slug' => $r->slug, 'name' => $r->name, 'child' => true])))->all(),
            'activities' => Activity::whereHas('trips')->orderBy('sort')->orderBy('name')->get(['slug', 'name'])->toArray(),
            'difficulties' => Difficulty::orderBy('level')->get(['slug', 'name'])->toArray(),
            'tiers' => Tier::orderBy('sort')->get(['slug', 'name'])->toArray(),
        ];
    }

    #[Computed]
    public function trips()
    {
        return $this->query()->with(['destination', 'difficulty', 'media'])->paginate(12);
    }

    public function activeCount(): int
    {
        return collect([$this->q, $this->destination, $this->activity, $this->difficulty, $this->tier, $this->duration, $this->fixed])->filter()->count();
    }

    private function query(): Builder
    {
        $query = Trip::published();

        if ($this->q !== '') {
            $term = '%'.str($this->q)->limit(80, '')->toString().'%';
            $query->where(fn ($q) => $q->where('title', 'like', $term)->orWhere('excerpt', 'like', $term));
        }

        if ($this->destination !== '' && $dest = Destination::with('children')->where('slug', $this->destination)->first()) {
            $ids = $dest->children->pluck('id')->push($dest->id);
            $query->whereHas('destinations', fn ($q) => $q->whereIn('destinations.id', $ids));
        }

        if ($this->activity !== '') {
            $query->whereHas('activities', fn ($q) => $q->where('slug', $this->activity));
        }

        if ($this->difficulty !== '') {
            $query->whereHas('difficulty', fn ($q) => $q->where('slug', $this->difficulty));
        }

        if ($this->tier !== '') {
            $query->whereHas('tiers', fn ($q) => $q->where('slug', $this->tier));
        }

        if (isset(self::DURATIONS[$this->duration])) {
            [$min, $max] = array_map('intval', explode('-', $this->duration));
            $query->whereBetween('duration_days', [$min, $max]);
        }

        if ($this->fixed) {
            $query->where('is_fixed_departure', true);
        }

        return match ($this->sort) {
            'price-asc' => $query->orderByRaw('COALESCE(sale_price, price_from) IS NULL')->orderByRaw('COALESCE(sale_price, price_from) asc'),
            'price-desc' => $query->orderByRaw('COALESCE(sale_price, price_from) desc'),
            'duration-asc' => $query->orderBy('duration_days'),
            'duration-desc' => $query->orderByDesc('duration_days'),
            default => $query->orderByDesc('is_featured')->orderByDesc('is_top_selling')->orderByDesc('is_popular')->orderBy('sort')->orderByDesc('published_at'),
        };
    }

    public function render()
    {
        return view('livewire.trip-finder');
    }
}
