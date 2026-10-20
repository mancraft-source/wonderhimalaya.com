<?php

namespace App\Services\WordPress\Importers;

use App\Models\TeamMember;
use App\Services\WordPress\HtmlCleaner;
use App\Services\WordPress\ImportStats;
use App\Services\WordPress\TitleCleaner;
use App\Services\WordPress\WordPressSource;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Str;

/**
 * Team members are child pages of /our-team/ built with Elementor. The role
 * and social links live on the team cards (our-team and travel-guide pages);
 * bio, experience and photo come from the profile page itself.
 */
class TeamImporter extends Importer
{
    protected const LANGUAGES = ['English', 'Nepali', 'Hindi', 'Chinese', 'Mandarin', 'Spanish', 'French', 'German', 'Japanese', 'Korean', 'Italian', 'Russian', 'Tibetan', 'Portuguese', 'Dutch', 'Polish'];

    /** @var array<string, array{role: ?string, social: array<string, string>, order: int, image: ?string}>|null */
    protected ?array $cards = null;

    public function import(): ImportStats
    {
        $members = collect($this->source->pages())
            ->filter(fn ($p) => $this->source->isTeamPage($p))
            ->sortBy(fn ($p) => [$this->cards()[$p['slug']]['order'] ?? 999, $p['menu_order'] ?? 0, $p['id']]);

        $sort = 0;

        foreach ($members as $wp) {
            $this->log('Team member '.$wp['id'].': '.$wp['slug']);
            $this->importMember($wp, ++$sort);
        }

        return $this->stats;
    }

    public function importMember(array $wp, int $sort = 0): TeamMember
    {
        $profile = $this->parseProfile($wp['content']['rendered'] ?? '');
        $card = $this->cards()[$wp['slug']] ?? ['role' => null, 'social' => [], 'image' => null];
        $name = TitleCleaner::text($wp['title']['rendered'] ?? '');
        $bioText = HtmlCleaner::text($profile['bio']);
        $role = $card['role'] ?? self::roleFromBio($bioText);

        $member = $this->locate(TeamMember::class, (int) $wp['id'], 'slug', WordPressSource::teamSlug($wp['slug']));

        $member->fill([
            'name' => $name,
            'slug' => $this->uniqueSlug(TeamMember::class, WordPressSource::teamSlug($wp['slug']), $member->id),
            'role' => $role ? Str::limit($role, 250, '') : null,
            'excerpt' => $bioText !== '' ? Str::words($bioText, 30, '…') : null,
            'bio' => $profile['bio'] ?: null,
            'experience_years' => $profile['years'] ?? self::yearsFromBio($bioText),
            'languages' => self::languages($bioText) ?: null,
            'certifications' => null,
            'social' => $card['social'] ?: null,
            'is_guide' => (bool) preg_match('/\bguide\b/i', (string) $role),
            'is_published' => true,
            'sort' => $sort,
            'seo' => WordPressSource::seo($wp),
            'wp_id' => (int) $wp['id'],
        ])->save();

        $this->stats->record('team_members', $member);

        $this->attachPhoto($member, $wp, $profile, $card);

        return $member;
    }

    protected function attachPhoto(TeamMember $member, array $wp, array $profile, array $card): void
    {
        if (! $this->media->enabled()) {
            return;
        }

        $photo = $this->source->mediaItem((int) ($wp['featured_media'] ?? 0));
        $photoUrl = $photo['url'] ?? $profile['image'] ?? $card['image'];

        if ($photoUrl) {
            $this->media->attach($member, 'photo', $photoUrl, ($photo['alt'] ?? $profile['image_alt']) ?: $member->name, $photo['id'] ?? null);
        }
    }

    public function importMedia(TeamMember $member): void
    {
        if ($wp = $this->source->pages()[$member->wp_id] ?? null) {
            $profile = $this->parseProfile($wp['content']['rendered'] ?? '');
            $this->attachPhoto($member, $wp, $profile, $this->cards()[$wp['slug']] ?? ['image' => null]);
            $this->stats->add('team (media)', 'updated');
        }
    }

    /**
     * @return array{bio: string, years: ?int, image: ?string, image_alt: ?string}
     */
    public function parseProfile(string $html): array
    {
        $cleaner = new HtmlCleaner;
        $dom = $cleaner->load(preg_replace('/<!--.*?-->/s', '', $html) ?? $html);
        $xpath = new DOMXPath($dom);

        $image = null;
        $alt = null;

        foreach ($xpath->query('//img') ?: [] as $img) {
            /** @var DOMElement $img */
            $src = $img->getAttribute('src') ?: $img->getAttribute('data-src');

            if ($src && ! preg_match('/star\.webp|\.svg$|trustindex|googleusercontent/i', $src)) {
                $image = $src;
                $alt = TitleCleaner::text($img->getAttribute('alt')) ?: null;
                break;
            }
        }

        // Paragraphs before the "What People Say About Him" section form the bio.
        $paragraphs = [];

        foreach ($xpath->query('//h1|//h2|//h3|//p') ?: [] as $node) {
            $text = TitleCleaner::text($node->textContent);

            if (in_array($node->nodeName, ['h1', 'h2', 'h3'], true)) {
                if ($paragraphs && preg_match('/what people say|testimonial|review/i', $text)) {
                    break;
                }

                continue;
            }

            if ($text !== '' && mb_strlen($text) > 40) {
                $paragraphs[] = '<p>'.e($text).'</p>';
            }
        }

        $years = null;

        if (preg_match('/(\d{1,2})\s*\+?\s*years?\s+(in|of)\s+(tourism|experience|guiding)/i', HtmlCleaner::text($html), $m)) {
            $years = (int) $m[1];
        }

        return ['bio' => implode("\n", $paragraphs), 'years' => $years, 'image' => $image, 'image_alt' => $alt];
    }

    public static function roleFromBio(string $bio): ?string
    {
        $patterns = [
            '/\bis\s+(?:the|one of the|a|an)\s+((?:co-?\s?founder|founder|managing director|director|ceo)[^,.]*)/i',
            '/\bworks?\s+as\s+(?:a|an|our|the)\s+([a-z][a-z\s\/&-]{3,60}?)(?:\s+at\b|,|\.)/i',
            '/\b(?:licensed|experienced|professional)\s+(trekking guide|tour guide|guide)\b/i',
            '/\b(trekking guide|tour guide|mountain guide)\b/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $bio, $m)) {
                $role = Str::of($m[1])->squish()->replaceMatches('/^co-?\s?founder/i', 'Co-Founder')->toString();

                return Str::title($role) === $role ? $role : self::titleRole($role);
            }
        }

        return preg_match('/\bguide\b/i', $bio) ? 'Trekking Guide' : null;
    }

    protected static function titleRole(string $role): string
    {
        return collect(explode(' ', $role))
            ->map(fn ($w, $i) => $i > 0 && in_array(strtolower($w), ['and', 'of', 'at', 'the'], true) ? strtolower($w) : Str::ucfirst($w))
            ->implode(' ');
    }

    public static function yearsFromBio(string $bio): ?int
    {
        $words = ['two' => 2, 'three' => 3, 'four' => 4, 'five' => 5, 'six' => 6, 'seven' => 7, 'eight' => 8, 'nine' => 9, 'ten' => 10, 'twelve' => 12, 'fifteen' => 15, 'twenty' => 20];

        if (preg_match('/(\d{1,2}|'.implode('|', array_keys($words)).')\s*\+?\s*years?/i', $bio, $m)) {
            return is_numeric($m[1]) ? (int) $m[1] : $words[strtolower($m[1])];
        }

        return null;
    }

    /** @return array<int, string> */
    public static function languages(string $bio): array
    {
        return array_values(array_filter(self::LANGUAGES, fn ($l) => preg_match('/\b'.$l.'\b/i', $bio)));
    }

    /**
     * Team cards ("Name / Role / Read More") on the our-team and travel-guide pages.
     *
     * @return array<string, array{role: ?string, social: array<string, string>, order: int, image: ?string}>
     */
    public function cards(): array
    {
        if ($this->cards !== null) {
            return $this->cards;
        }

        $this->cards = [];
        $order = 0;
        $listing = collect($this->source->pages())
            ->filter(fn ($p) => in_array($p['slug'], ['our-team', 'travel-guide', 'about-us'], true))
            ->sortBy(fn ($p) => array_search($p['slug'], ['our-team', 'travel-guide', 'about-us'], true));

        foreach ($listing as $page) {
            $dom = (new HtmlCleaner)->load($page['content']['rendered'] ?? '');
            $xpath = new DOMXPath($dom);

            foreach ($xpath->query('//a[contains(@href, "/our-team/")]') ?: [] as $link) {
                /** @var DOMElement $link */
                if (! preg_match('#/our-team/([^/]+)/?#', $link->getAttribute('href'), $m) || isset($this->cards[$m[1]])) {
                    continue;
                }

                $card = $this->cardContainer($link);

                if (! $card) {
                    continue;
                }

                $heading = $xpath->query('.//h2|.//h3|.//h4', $card)->item(0);
                $role = null;

                foreach ($xpath->query('.//p|.//span[contains(@class,"designation")]|.//div[contains(@class,"designation")]', $card) ?: [] as $p) {
                    $text = TitleCleaner::text($p->textContent);

                    if ($text !== '' && ! preg_match('/read more|learn more/i', $text) && $text !== TitleCleaner::text($heading?->textContent)) {
                        $role = $text;
                        break;
                    }
                }

                $social = [];
                $scope = $card->parentNode instanceof DOMElement ? $card->parentNode : $card;

                foreach ($xpath->query('.//a[@href]', $scope) ?: [] as $a) {
                    $href = $a->getAttribute('href');

                    foreach (['facebook', 'instagram', 'linkedin', 'twitter', 'youtube', 'tiktok'] as $network) {
                        if (str_contains($href, $network.'.com/') && ! isset($social[$network]) && preg_match('#\.com/[^/?]+#', $href)) {
                            $social[$network] = $href;
                        }
                    }
                }

                $img = $xpath->query('.//img', $scope)->item(0);

                $this->cards[$m[1]] = [
                    'role' => $role,
                    'social' => $social,
                    'order' => ++$order,
                    'image' => $img instanceof DOMElement ? ($img->getAttribute('src') ?: null) : null,
                ];
            }
        }

        return $this->cards;
    }

    /** Walk up from the "Read More" link to the element that holds the card heading. */
    protected function cardContainer(DOMElement $link): ?DOMElement
    {
        $node = $link->parentNode;

        for ($i = 0; $i < 6 && $node instanceof DOMElement; $i++, $node = $node->parentNode) {
            $headings = 0;

            foreach (['h2', 'h3', 'h4'] as $tag) {
                $headings += $node->getElementsByTagName($tag)->length;
            }

            if ($headings === 1) {
                return $node;
            }

            if ($headings > 1) {
                return null;
            }
        }

        return null;
    }
}
