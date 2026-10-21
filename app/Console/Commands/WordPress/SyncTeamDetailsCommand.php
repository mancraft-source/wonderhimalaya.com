<?php

namespace App\Console\Commands\WordPress;

use App\Models\TeamMember;
use App\Services\WordPress\HtmlCleaner;
use DOMXPath;

/**
 * Fills the profile sections of team pages from the live site: the highlight list
 * (e.g. "Served 1,000+ clients"), the "What people say" paragraph and guest quotes.
 */
class SyncTeamDetailsCommand extends WordPressCommand
{
    protected $signature = 'wp:sync-team-details {--fresh : Bypass the response cache}';

    protected $description = 'Sync team member highlights, praise and guest quotes from the live site';

    public function handle(): int
    {
        $this->prepare();
        $pages = collect($this->source()->pages())->keyBy('id');
        $updated = 0;

        foreach (TeamMember::whereNotNull('wp_id')->get() as $member) {
            $wp = $pages->get($member->wp_id);
            if (! $wp) {
                continue;
            }

            $sections = $this->sections((string) ($wp['content']['rendered'] ?? ''));
            $member->fill([
                'highlights' => $sections['highlights'] ?: $member->highlights,
                'praise' => $sections['praise'] ?: $member->praise,
                'quotes' => $sections['quotes'] ?: $member->quotes,
            ])->save();
            $updated++;
        }

        $this->info("Updated {$updated} team members.");

        return self::SUCCESS;
    }

    /** @return array{highlights: array<int, string>, praise: ?string, quotes: array<int, string>} */
    private function sections(string $html): array
    {
        $dom = app(HtmlCleaner::class)->load($html);
        $nodes = (new DOMXPath($dom))->query('//h1|//h2|//h3|//p|//li');
        $after = false;
        $out = ['highlights' => [], 'praise' => null, 'quotes' => []];

        foreach ($nodes as $node) {
            $text = trim(preg_replace('/\s+/u', ' ', html_entity_decode($node->textContent)));
            if ($text === '') {
                continue;
            }
            if (in_array($node->nodeName, ['h1', 'h2', 'h3'], true)) {
                $after = $after || str_contains(strtolower($text), 'say');

                continue;
            }
            if ($node->nodeName === 'li') {
                $after ? $out['quotes'][] = $text : $out['highlights'][] = $text;
            } elseif ($after && $out['praise'] === null) {
                $out['praise'] = $text;
            }
        }

        $out['highlights'] = array_values(array_unique($out['highlights']));
        $out['quotes'] = array_values(array_unique($out['quotes']));

        return $out;
    }
}
