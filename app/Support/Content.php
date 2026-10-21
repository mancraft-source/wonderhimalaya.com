<?php

namespace App\Support;

use App\Models\Page;
use App\Models\Setting;
use App\Models\Trip;
use Illuminate\Support\Facades\Storage;

/**
 * Editable copy for designed templates (homepage, trip page extras, About / Why / Sustainability /
 * Travel-guide hub). Defaults below are the launch copy; editors override any field in the admin.
 *
 * Storage contract (shared with the Filament forms):
 *  - Setting "home"        => self::homeDefaults() shape
 *  - Setting "trip_page"   => self::tripPageDefaults() shape
 *  - Page::$content (JSON) => self::pageDefaults($template) shape for templates about|why|sustainability|guide-hub
 *  - Trip::$packing        => list of {group: string, items: string[]}; Trip::$itinerary_note => string
 *  - Setting "b2b" / "tailor_made" / "contact" => self::landingDefaults($key) shape
 * Image fields hold a path on the "public" disk (FileUpload) and are optional.
 */
final class Content
{
    /** Heroicon (outline) names editors can pick for tiles. @var array<string, string> */
    public const ICONS = [
        'user-group' => 'Team / guides',
        'globe-asia-australia' => 'Globe / sustainability',
        'adjustments-horizontal' => 'Tailored / settings',
        'heart' => 'Heart / care',
        'shield-check' => 'Shield / safety',
        'clock' => 'Clock / 24-7',
        'light-bulb' => 'Idea',
        'sparkles' => 'Sparkles / unique',
        'bolt' => 'Bolt / fast',
        'map' => 'Map / route',
        'home-modern' => 'Home / lodges',
        'lifebuoy' => 'Lifebuoy / support',
        'trophy' => 'Trophy / award',
        'star' => 'Star',
        'sun' => 'Sun / season',
        'camera' => 'Camera',
        'briefcase' => 'Briefcase / B2B',
        'chat-bubble-left-right' => 'Chat',
        'banknotes' => 'Money / value',
        'check-badge' => 'Badge / licensed',
    ];

    /** @return array<string, mixed> */
    public static function home(): array
    {
        return self::merge(self::homeDefaults(), Setting::get('home', []));
    }

    /** @return array<string, mixed> */
    public static function tripPage(): array
    {
        return self::merge(self::tripPageDefaults(), Setting::get('trip_page', []));
    }

    /** @return array<string, mixed> */
    public static function page(Page $page): array
    {
        return self::merge(self::pageDefaults((string) $page->template), $page->content ?? []);
    }

    /** Editable copy for the route-based landing pages: b2b, tailor_made, contact. @return array<string, mixed> */
    public static function landing(string $key): array
    {
        return self::merge(self::landingDefaults($key), Setting::get($key, []));
    }

    /** @return array<string, mixed> */
    public static function landingDefaults(string $key): array
    {
        return match ($key) {
            'b2b' => [
                'hero_eyebrow' => 'For agencies & tour operators',
                'hero_title' => 'Your local partner',
                'hero_title_em' => 'in the Himalaya.',
                'hero_subtitle' => 'Reliable, fully-licensed ground operations across Nepal, Tibet and Bhutan — delivered under your brand, at net rates, with a Kathmandu team that answers in hours, not days.',
                'credentials' => ['All permits, TIMS & park fees handled', 'Licensed, first-aid trained guides', 'Porter welfare & insurance on every trip', 'Transparent net-rate pricing'],
                'services_eyebrow' => 'What we do',
                'services_title' => 'Ground operations, done properly',
                'services_intro' => 'Hand us the guest, the dates and the brief. We handle permits, logistics, guides, lodges, flights and on-trip care — and keep you in the loop throughout.',
                'services' => [
                    ['icon' => 'briefcase', 'title' => 'White-label ground ops', 'text' => 'Your brand on every document. We operate silently behind you — arrival to departure.'],
                    ['icon' => 'sparkles', 'title' => 'Bespoke FIT curation', 'text' => 'Private itineraries for couples, families and high-end clients, quoted within 24 hours.'],
                    ['icon' => 'user-group', 'title' => 'Small-group expeditions', 'text' => 'Fixed departures you can sell, treks and climbs to 6,000 m+, with experienced climbing Sherpas.'],
                    ['icon' => 'chat-bubble-left-right', 'title' => 'MICE & corporate', 'text' => 'Incentive trips, offsites and group events in Kathmandu, Pokhara and beyond.'],
                ],
                'destinations_eyebrow' => 'Destinations we cover',
                'destinations_title' => 'Four countries, one trusted team',
                'integrity_eyebrow' => 'Operational integrity',
                'integrity_title' => 'Numbers partners rely on',
                'integrity' => [['value' => '24h', 'label' => 'Quote turnaround'], ['value' => '24/7', 'label' => 'On-trip support line']],
                'form_eyebrow' => 'Contact & partnership',
                'form_title' => "Let's build something together.",
                'form_intro' => 'Tell us about your business. Our partnerships desk replies within one business day with net rates, sample programmes and terms.',
            ],
            'tailor_made' => [
                'eyebrow' => 'Tailor-made journeys',
                'title' => 'Your trip, drawn by hand.',
                'intro' => 'Answer five quick questions. A Kathmandu-based specialist — someone who has walked these trails — will design a private itinerary around you, usually within a day.',
                'benefits' => [
                    ['icon' => 'user-group', 'text' => 'Private guide & your own dates'],
                    ['icon' => 'adjustments-horizontal', 'text' => 'Flexible pace, extra acclimatisation, any lodge standard'],
                    ['icon' => 'shield-check', 'text' => "No obligation — pay a deposit only when it's perfect"],
                    ['icon' => 'lifebuoy', 'text' => '24/7 support from our Thamel office while you travel'],
                ],
            ],
            'contact' => [
                'eyebrow' => 'Contact',
                'title' => 'Namaste —',
                'title_em' => "let's talk.",
                'intro' => 'Questions about a route, the right season, fitness or permits? Our Thamel team replies personally, usually within a few hours.',
                'map_query' => 'Thamel, Kathmandu, Nepal',
            ],
            default => [],
        };
    }

    /** @return array<int, array{group: string, items: array<int, string>}> */
    public static function packingFor(Trip $trip): array
    {
        $own = collect($trip->packing ?? [])->filter(fn ($g) => filled($g['group'] ?? null) && ! empty($g['items']))->values()->all();
        if ($own !== []) {
            return $own;
        }

        return PackingList::appliesTo($trip) ? self::tripPage()['packing_default'] : [];
    }

    /** Public URL for an optional image field (storage path, absolute URL or null). */
    public static function image(mixed $value, ?string $fallback = null): ?string
    {
        if (is_array($value)) {
            $value = reset($value) ?: null;
        }

        if (blank($value)) {
            return $fallback;
        }

        return str_starts_with((string) $value, 'http') || str_starts_with((string) $value, '/')
            ? (string) $value
            : Storage::disk('public')->url((string) $value);
    }

    /**
     * Saved values win; blank strings/empty lists fall back to defaults so a half-filled form never blanks a section.
     *
     * @param  array<string, mixed>  $defaults
     * @param  array<string, mixed>|null  $saved
     * @return array<string, mixed>
     */
    public static function merge(array $defaults, ?array $saved): array
    {
        foreach ($saved ?? [] as $key => $value) {
            if ($value === null || $value === '' || $value === []) {
                continue;
            }

            $defaults[$key] = is_array($value) && isset($defaults[$key]) && is_array($defaults[$key]) && ! array_is_list($value) && ! array_is_list($defaults[$key])
                ? self::merge($defaults[$key], $value)
                : $value;
        }

        return $defaults;
    }

    /**
     * The part of a saved form that differs from the defaults (so improving default copy later still
     * reaches every untouched field). Lists are compared as a whole.
     *
     * @param  array<string, mixed>  $state
     * @param  array<string, mixed>  $defaults
     * @return array<string, mixed>
     */
    public static function overrides(array $state, array $defaults): array
    {
        $changed = [];

        foreach ($state as $key => $value) {
            $default = $defaults[$key] ?? null;

            if (is_array($value) && is_array($default) && ! array_is_list($value) && ! array_is_list($default) && $value !== []) {
                $nested = self::overrides($value, $default);
                if ($nested !== []) {
                    $changed[$key] = $nested;
                }

                continue;
            }

            if (! array_key_exists($key, $defaults) || self::normalise($value) !== self::normalise($default)) {
                $changed[$key] = $value;
            }
        }

        return $changed;
    }

    private static function normalise(mixed $value): mixed
    {
        if (is_string($value)) {
            return trim(str_replace("\r\n", "\n", $value));
        }

        return is_array($value) ? array_map(self::normalise(...), $value) : $value;
    }

    /** @return array<string, mixed> */
    public static function homeDefaults(): array
    {
        return [
            'hero_eyebrow' => 'Nepal · Tibet · Bhutan',
            'hero_title' => 'Walk among',
            'hero_title_em' => 'giants.',
            'hero_subtitle' => 'Treks, climbs and quiet journeys through the high Himalaya — designed and guided by a small Kathmandu team who call these mountains home.',
            // [{trip_id, eyebrow, title, image}] — empty = curated defaults in HomeController
            'hero_slides' => [],
            'ticker' => [],
            'intro_eyebrow' => 'Welcome to Wonder Himalaya',
            'intro_title' => 'A small Thamel team, a lifetime on these trails.',
            'intro_lead' => "For years we have done one thing — taking people into the mountains and bringing them back with stories they'll tell for the rest of their lives.",
            'intro_body' => 'From the teahouses of the Everest region to the monastery corridors of Lhasa, from the rhododendron forests of the Annapurna Circuit to the fortress towns of Bhutan — this part of the world is what we know, and sharing it is what we do.',
            'intro_badge' => 'WANDER ALWAYS · WONDER OFTEN · ',
            'collage_trip_ids' => [],
            'moods_eyebrow' => 'Travel by feeling',
            'moods_title' => 'How do you want the mountains to feel?',
            // [{activity_id, title, line, image}] — empty = defaults in HomeController
            'moods' => [],
            'signature_eyebrow' => 'Signature journeys',
            'signature_title' => 'The trails our travellers love most',
            'signature_intro' => "Classics we've walked hundreds of times — and still can't get enough of.",
            'signature_trip_ids' => [],
            'feature_trip_id' => null,
            'regions_eyebrow' => 'Choose your mountains',
            'regions_title' => 'Nepal, valley by valley',
            'regions_intro' => 'Sherpa villages and glaciers, rhododendron forests, Tibetan-Buddhist hidden valleys — each region has its own soul.',
            'departures_eyebrow' => 'Fixed group departures',
            'departures_title' => 'Travel solo, trek together',
            'departures_intro' => 'Small, guaranteed groups on set dates — shared costs and the same expert guiding as our private journeys.',
            'why_eyebrow' => 'Why Wonder Himalaya',
            'why_title' => 'The trusted partner in the Himalaya',
            'why_award_text' => 'Officially recognised by travellers — a true reflection of the confidence people place in us, journey after journey.',
            'why_tiles' => [
                ['icon' => 'user-group', 'title' => 'Guided by experts', 'text' => 'Licensed, first-aid trained local guides who grew up on these trails.'],
                ['icon' => 'globe-asia-australia', 'title' => 'Sustainable travel', 'text' => 'Local guides and lodges, fair treatment of porters, and leave-no-trace on every trek.'],
                ['icon' => 'adjustments-horizontal', 'title' => 'Tailored to you', 'text' => 'Extra acclimatisation, lodge upgrades, a heli return — every itinerary bends to you.'],
                ['icon' => 'heart', 'title' => '24/7 hospitality', 'text' => 'From airport pick-up to the farewell dinner, our Thamel office is always on call.'],
            ],
            'reviews_eyebrow' => 'In their words',
            'reviews_title' => '‘Memorable’, ‘exciting’, ‘unforgettable’',
            'journal_eyebrow' => 'The Journal',
            'journal_title' => 'Notes from the trail',
        ];
    }

    /** @return array<string, mixed> */
    public static function tripPageDefaults(): array
    {
        return [
            'itinerary_note' => 'Mountain itineraries can change with weather, trail and flight conditions. Your guide always puts safety and acclimatisation first.',
            'group_discount_note' => 'Group discounts apply automatically — the more of you travel together, the less each person pays.',
            'packing_note' => "Sleeping bags and down jackets can be borrowed from us in Kathmandu. Luggage you don't need on the trek can be stored at your hotel.",
            'packing_default' => collect(PackingList::default())->map(fn (array $items, string $group) => ['group' => $group, 'items' => $items])->values()->all(),
            'trust' => [
                ['icon' => 'shield-check', 'title' => 'Book with confidence', 'text' => 'Secure your place with a small deposit; flexible changes and a risk-free booking policy.'],
                ['icon' => 'user-group', 'title' => 'Local, licensed guides', 'text' => 'First-aid trained guides from the mountains themselves — and porters who are fairly paid and insured.'],
                ['icon' => 'globe-asia-australia', 'title' => 'Responsible travel', 'text' => 'Local lodges, community-first spending and a leave-no-trace ethic on every trail.'],
                ['icon' => 'lifebuoy', 'title' => '24/7 support', 'text' => 'Our Thamel office is on call around the clock while you travel.'],
            ],
        ];
    }

    /** @return array<string, mixed> */
    public static function pageDefaults(string $template): array
    {
        return match ($template) {
            'about' => [
                'hero_eyebrow' => 'Our story',
                'hero_title' => 'Five friends, one love:',
                'hero_title_em' => 'the Himalaya.',
                'hero_subtitle' => "A company built by travellers, for travellers — based in the heart of Kathmandu's Thamel district.",
                'hero_image' => null,
                'story_eyebrow' => 'About Wonder Himalaya',
                'story_title' => "More than a trip — a story you'll treasure for a lifetime.",
                'story_body' => '<p>At Wonder Himalaya, we are a passionate group of friends brought together by a shared love for adventure, exploration and the breathtaking beauty of the Himalayas.</p><p>From our base in Thamel, we craft unforgettable journeys across Nepal, Bhutan and Tibet — trekking, cultural exploration, luxury travel and heli tours for every type of traveller, from those seeking rugged adventure to those desiring luxurious escapes.</p><p>Through responsible and sustainable tourism, we work to preserve Nepal\'s natural beauty, support local communities and connect our guests to the soul of the mountains.</p>',
                'story_image' => null,
                'story_badge_title' => 'Thamel',
                'story_badge_text' => 'Kathmandu, Nepal',
                'vision_lead' => 'The bridge between the traveller and the unexplored heart of the Himalaya.',
                'vision_body' => 'We bring travellers closer to the beauty, culture and traditions of Nepal, Bhutan and Tibet in a way that fosters mutual respect, cultural understanding and a deep connection to the places and people we encounter — growing sustainably, journey by journey.',
                'mission_lead' => 'Meaningful connections between our guests and the land, culture and people of Nepal.',
                'mission_body' => 'Every itinerary is designed to be safe, personal and enriching — so that each adventure leaves you with more than memories: with an understanding of, and a connection to, the places we visit, and a positive impact on the destinations we cherish.',
                'values_eyebrow' => 'Why choose us',
                'values_title' => 'Your trusted partner in the Himalaya',
                'values' => [
                    ['icon' => 'user-group', 'title' => 'Guided by experts', 'text' => 'Seasoned professionals who lead you to the hidden gems.'],
                    ['icon' => 'adjustments-horizontal', 'title' => 'Tailored experiences', 'text' => 'Your dream adventure, personalised to perfection.'],
                    ['icon' => 'globe-asia-australia', 'title' => 'Sustainable travel', 'text' => 'Explore responsibly while preserving the beauty of nature.'],
                    ['icon' => 'heart', 'title' => 'Unrivalled hospitality', 'text' => 'The warmth and care of our team at every step.'],
                ],
                'team_eyebrow' => 'Meet the team',
                'team_title' => 'Masters of the mountains',
                'team_intro' => 'The passionate explorers, expert guides and dedicated professionals behind every Wonder Himalaya journey.',
                'faq_eyebrow' => 'Your questions',
                'faq_title' => 'Answered with care',
                'faqs' => [
                    ['question' => 'What is the best time to visit Nepal?', 'answer' => 'Nepal is a year-round destination. Each season offers something different, from lush greenery during the monsoon to crystal-clear Himalayan views in autumn and spring.'],
                    ['question' => 'Can I customise my trip?', 'answer' => 'Absolutely. We specialise in personalised itineraries built around your interests, preferences and schedule.'],
                    ['question' => 'Do you cater to solo travellers or small groups?', 'answer' => 'Yes. Whether you travel alone or with friends, we make the experience seamless — and our fixed departures are a great way to join a small group.'],
                    ['question' => 'How physically challenging are the treks and tours?', 'answer' => "We offer options for every fitness level, from easy cultural tours to strenuous high-altitude treks. We'll help you choose the right one."],
                    ['question' => 'What safety measures do you provide?', 'answer' => 'Experienced guides, first-aid kits, careful acclimatisation planning and 24/7 support from our Kathmandu office — your safety is our top priority.'],
                    ['question' => 'Do I need special permits for trekking in Nepal?', 'answer' => 'Most treks need a TIMS card and area-specific permits. We handle all the paperwork for you.'],
                    ['question' => 'Are meals and accommodation included?', 'answer' => 'Most packages include meals and accommodation — tea-houses or camping on treks, and exclusive stays on our luxury journeys.'],
                ],
            ],
            'why' => [
                'hero_eyebrow' => 'Why Wonder Himalaya',
                'hero_title' => "There's an agency on every street in Kathmandu.",
                'hero_title_em' => "Here's why travellers choose us.",
                'hero_image' => null,
                'lead_eyebrow' => 'The simple answer',
                'lead_text' => 'Proper hospitality — and a team that helps you through every question, before, during and after your trip.',
                'reasons' => [
                    ['icon' => 'clock', 'title' => '24/7 service', 'text' => 'Our Thamel office is open around the clock — drop in or call any time for questions, help on the trail or last-minute planning.'],
                    ['icon' => 'shield-check', 'title' => 'Reputable & trustworthy', 'text' => 'Hundreds of travellers have trusted us with their Himalayan journeys — and many come back. We treat every guest as family.'],
                    ['icon' => 'adjustments-horizontal', 'title' => 'Tailored itineraries', 'text' => 'Every itinerary can be reshaped around you: pace, lodges, extra acclimatisation days, a helicopter return. Flexibility is our default.'],
                    ['icon' => 'heart', 'title' => 'Social contributions', 'text' => 'A share of every booking goes to charitable work in Nepal. When you travel with us, you give back too.'],
                    ['icon' => 'light-bulb', 'title' => 'Young, fresh thinking', 'text' => 'A young founding team with fresh ideas — balancing business with responsibility to society and to sustainable tourism.'],
                    ['icon' => 'sparkles', 'title' => 'Unique packages', 'text' => 'From helicopter tours to Everest and Annapurna base camps to cultural tours and peak climbing — something for every traveller.'],
                    ['icon' => 'bolt', 'title' => 'Last-minute booking', 'text' => 'Plans changed? We accept bookings up to two days before departure — and will still make it wonderful.'],
                ],
                'reviews_eyebrow' => "Don't take our word for it",
                'reviews_title' => 'What travellers say',
            ],
            'sustainability' => [
                'hero_eyebrow' => 'Sustainable tourism',
                'hero_title' => 'Leave the mountains',
                'hero_title_em' => 'better than we found them.',
                'hero_image' => null,
                'intro_eyebrow' => 'Why it matters',
                'intro_title' => 'The Himalaya is what our guests come for — and what we must protect.',
                'intro_body' => '<p>Tourism is our livelihood, and the landscapes, villages and wildlife of the Himalaya are what draw travellers here. As visitor numbers grow, the environment pays the price unless we travel differently.</p><p>So we follow sustainable tourism in everything we do — harming nature as little as possible, supporting local communities, and making sure the places we visit stay beautiful and healthy for the generations to come. And we ask every traveller to do the same.</p>',
                'pillars' => [
                    ['icon' => 'globe-asia-australia', 'title' => 'Eco-friendly destinations', 'text' => 'We champion places that protect their environment and wildlife — the Annapurna Conservation Area, Rara Lake, Chitwan and Langtang National Parks.'],
                    ['icon' => 'home-modern', 'title' => 'Local communities first', 'text' => 'Local guides on most treks, homestays and family-run lodges, and we encourage every traveller to buy local food and crafts.'],
                    ['icon' => 'map', 'title' => 'Low-impact itineraries', 'text' => 'We favour trekking over flying or driving wherever we can, and choose sustainable accommodation and lodges.'],
                    ['icon' => 'heart', 'title' => 'Ethical wildlife practice', 'text' => 'No elephant rides, no activities that harm animals — ever. Wildlife is something to admire, not use.'],
                ],
                'tips_title' => 'Travel lightly — a few simple habits',
                'tips' => ['Carry a refillable bottle and purification tablets', 'Pack out what you pack in', 'Stay on marked trails', 'Ask before photographing people', 'Dress modestly at temples & monasteries', 'Buy local — tea-houses, crafts, food'],
                'cta_label' => 'Plan a responsible trip',
            ],
            'guide-hub' => [
                'hero_eyebrow' => 'Travel guide',
                'hero_title' => 'Adventure awaits those who dare to take',
                'hero_title_em' => 'the first step.',
                'hero_subtitle' => 'Everything you need to prepare — visas, kit, seasons, insurance and how hard each trek really is.',
                'hero_image' => null,
                'guides_eyebrow' => 'Your safety, our priority',
                'guides_title' => 'Meet our guides',
                'guides_intro' => "Licensed, experienced and first-aid trained — many grew up in the mountain villages you'll walk through.",
            ],
            default => [],
        };
    }
}
