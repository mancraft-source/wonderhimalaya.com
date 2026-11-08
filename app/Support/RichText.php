<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use Illuminate\Support\Str;

/**
 * Tidies imported WordPress HTML at render time, so editors' content stays untouched in the database:
 * newline-separated "lists" inside paragraphs become real lists, runs of image-only paragraphs become
 * a gallery, over-long headings become callouts, and h2/h3 get anchor ids for an "On this page" index.
 */
class RichText
{
    /** Lines longer than this are prose, not list items. */
    private const MAX_ITEM_LENGTH = 90;

    /** Headings longer than this were used as a styled statement, not as a heading. */
    private const MAX_HEADING_LENGTH = 140;

    public static function normalize(?string $html): string
    {
        $html = trim((string) $html);

        if ($html === '') {
            return '';
        }

        $dom = self::load($html);
        $body = $dom->getElementsByTagName('body')->item(0);

        self::splitLineLists($dom, $body);
        self::calloutLongHeadings($dom, $body);
        self::groupImages($dom, $body);
        self::anchorHeadings($body);

        return self::save($body);
    }

    /**
     * The h2/h3 outline of already-normalized HTML.
     *
     * @return array<int, array{id: string, text: string, level: int}>
     */
    public static function outline(string $normalizedHtml): array
    {
        preg_match_all('/<h([23])[^>]*\bid="([^"]+)"[^>]*>(.*?)<\/h\1>/is', $normalizedHtml, $matches, PREG_SET_ORDER);

        return array_map(fn (array $m) => [
            'id' => $m[2],
            'text' => trim(html_entity_decode(strip_tags($m[3]), ENT_QUOTES | ENT_HTML5, 'UTF-8')),
            'level' => (int) $m[1],
        ], $matches);
    }

    public static function readingMinutes(string $html): int
    {
        return max(1, (int) round(str_word_count(strip_tags($html)) / 220));
    }

    private static function splitLineLists(DOMDocument $dom, DOMElement $body): void
    {
        foreach (self::children($body, 'p') as $p) {
            if ($p->getElementsByTagName('img')->length > 0 || ! str_contains($p->textContent, "\n")) {
                continue;
            }

            $lines = array_values(array_filter(array_map('trim', explode("\n", self::innerHtml($p))), fn ($l) => strip_tags($l) !== ''));

            if (count($lines) < 2) {
                continue;
            }

            $lead = array_shift($lines);
            $isList = count($lines) >= 2 && collect($lines)->every(fn ($l) => mb_strlen(strip_tags($l)) <= self::MAX_ITEM_LENGTH);

            if (! $isList) {
                self::replaceWithHtml($dom, $p, '<p>'.implode('<br>', array_merge([$lead], $lines)).'</p>');

                continue;
            }

            // "…the reasons are: First item" — the first item is fused onto the lead sentence.
            if (preg_match('/^(.*:)\s+([^:]{1,'.self::MAX_ITEM_LENGTH.'})$/su', $lead, $m)) {
                [$lead, $first] = [$m[1], $m[2]];
                array_unshift($lines, $first);
            } elseif (mb_strlen(strip_tags($lead)) <= self::MAX_ITEM_LENGTH && ! str_ends_with(strip_tags($lead), ':')) {
                array_unshift($lines, $lead);
                $lead = null;
            }

            $items = implode('', array_map(fn ($l) => '<li>'.rtrim($l, '.').'</li>', $lines));
            self::replaceWithHtml($dom, $p, ($lead !== null ? '<p>'.$lead.'</p>' : '').'<ul>'.$items.'</ul>');
        }
    }

    private static function calloutLongHeadings(DOMDocument $dom, DOMElement $body): void
    {
        foreach ([...self::children($body, 'h2'), ...self::children($body, 'h3'), ...self::children($body, 'h4')] as $h) {
            if (mb_strlen(trim($h->textContent)) > self::MAX_HEADING_LENGTH) {
                self::replaceWithHtml($dom, $h, '<blockquote class="callout"><p>'.self::innerHtml($h).'</p></blockquote>');
            }
        }
    }

    private static function groupImages(DOMDocument $dom, DOMElement $body): void
    {
        $run = [];
        $flush = function () use (&$run, $dom) {
            if (count($run) >= 2) {
                $gallery = $dom->createElement('div');
                $gallery->setAttribute('class', 'prose-gallery not-prose');
                $run[0]->parentNode->insertBefore($gallery, $run[0]);
                foreach ($run as $p) {
                    foreach (iterator_to_array($p->getElementsByTagName('img')) as $img) {
                        $gallery->appendChild($img);
                    }
                    $p->parentNode->removeChild($p);
                }
            }
            $run = [];
        };

        foreach (iterator_to_array($body->childNodes) as $node) {
            if ($node instanceof DOMElement && $node->tagName === 'p' && trim($node->textContent) === '' && $node->getElementsByTagName('img')->length > 0) {
                $run[] = $node;
            } elseif (! ($node->nodeType === XML_TEXT_NODE && trim($node->textContent) === '')) {
                $flush();
            }
        }
        $flush();
    }

    private static function anchorHeadings(DOMElement $body): void
    {
        $used = [];

        foreach ((new DOMXPath($body->ownerDocument))->query('.//h2|.//h3', $body) as $h) {
            if ($h->getAttribute('id') !== '') {
                $used[] = $h->getAttribute('id');

                continue;
            }
            $base = Str::slug(Str::limit($h->textContent, 60, '')) ?: 'section';
            $id = $base;
            for ($i = 2; in_array($id, $used, true); $i++) {
                $id = $base.'-'.$i;
            }
            $used[] = $id;
            $h->setAttribute('id', $id);
        }
    }

    private static function load(string $html): DOMDocument
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8"><html><body>'.$html.'</body></html>', LIBXML_HTML_NODEFDTD | LIBXML_NOERROR);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return $dom;
    }

    private static function save(DOMElement $body): string
    {
        return trim(self::innerHtml($body));
    }

    private static function innerHtml(DOMNode $node): string
    {
        $html = '';
        foreach ($node->childNodes as $child) {
            $html .= $node->ownerDocument->saveHTML($child);
        }

        return $html;
    }

    private static function replaceWithHtml(DOMDocument $dom, DOMElement $node, string $html): void
    {
        $fragment = self::load($html)->getElementsByTagName('body')->item(0);
        foreach (iterator_to_array($fragment->childNodes) as $child) {
            $node->parentNode->insertBefore($dom->importNode($child, true), $node);
        }
        $node->parentNode->removeChild($node);
    }

    /** @return array<int, DOMElement> */
    private static function children(DOMElement $parent, string $tag): array
    {
        return array_values(array_filter(iterator_to_array($parent->childNodes), fn ($n) => $n instanceof DOMElement && $n->tagName === $tag));
    }
}
