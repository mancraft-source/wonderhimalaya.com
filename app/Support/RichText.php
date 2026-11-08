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
        $html = str_replace('&amp;amp;', '&amp;', trim((string) $html));

        if ($html === '') {
            return '';
        }

        $dom = self::load($html);
        $body = $dom->getElementsByTagName('body')->item(0);

        self::dropBlankTextNodes($body);
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

    /**
     * Steps written as numbered headings ("Step 1: Choose a trip", "2. Pay the deposit"), when the outline numbers
     * at least three of them 1, 2, 3… in order. Anything else is not a process and yields no steps.
     *
     * @param  array<int, array{id: string, text: string, level: int}>  $outline
     * @return array<int, array{id: string, number: int, title: string}>
     */
    public static function stepsFromOutline(array $outline): array
    {
        $steps = [];

        foreach ($outline as $heading) {
            if (preg_match('/^(?:step\s*(\d{1,2})\s*[:.)\-–—]?|(\d{1,2})\s*[:.)\-–—])\s*(\S.*)$/iu', $heading['text'], $m)) {
                $steps[] = ['id' => $heading['id'], 'number' => (int) ($m[1] !== '' ? $m[1] : $m[2]), 'title' => $m[3]];
            }
        }

        $isSequence = $steps !== [] && array_column($steps, 'number') === range(1, count($steps));

        return count($steps) >= 3 && $isSequence ? $steps : [];
    }

    /**
     * Marks a process in normalized HTML: a step with a `before` phrase gets a new step heading in front of the first
     * top-level element whose text starts with that phrase; a step without one decorates the existing heading with its id.
     * Steps whose target is not found are left out, so the caller can render them elsewhere.
     *
     * @param  array<int, array{id: string, number: int, title: string, before?: string|null}>  $steps
     */
    public static function markSteps(string $normalizedHtml, array $steps, int $total): string
    {
        if ($normalizedHtml === '' || $steps === []) {
            return $normalizedHtml;
        }

        $dom = self::load($normalizedHtml);
        $body = $dom->getElementsByTagName('body')->item(0);
        $xpath = new DOMXPath($dom);

        foreach ($steps as $step) {
            $label = '<span class="process-step__num" aria-hidden="true">'.$step['number'].'</span>'
                .'<span class="process-step__text"><span class="process-step__of">Step '.$step['number'].' of '.$total.'</span>'.e($step['title']).'</span>';

            if (! empty($step['before'])) {
                $target = collect(iterator_to_array($body->childNodes))->first(fn ($node) => $node instanceof DOMElement
                    && str_starts_with(self::plainText($node->textContent), self::plainText($step['before'])));

                if ($target !== null) {
                    $heading = self::load('<h2 id="'.e($step['id']).'" class="process-step">'.$label.'</h2>')->getElementsByTagName('h2')->item(0);
                    $body->insertBefore($dom->importNode($heading, true), $target);
                }

                continue;
            }

            $heading = $xpath->query('.//*[@id="'.str_replace('"', '', $step['id']).'"]', $body)->item(0);

            if ($heading instanceof DOMElement) {
                $heading->setAttribute('class', trim($heading->getAttribute('class').' process-step'));
                while ($heading->firstChild) {
                    $heading->removeChild($heading->firstChild);
                }
                foreach (iterator_to_array(self::load($label)->getElementsByTagName('body')->item(0)->childNodes) as $child) {
                    $heading->appendChild($dom->importNode($child, true));
                }
            }
        }

        return self::save($body);
    }

    /**
     * "Label: value" bullet lists (bank details, trip facts) as a definition card; a trailing "Note: …" item becomes its footnote.
     */
    public static function detailCards(string $normalizedHtml): string
    {
        if ($normalizedHtml === '') {
            return '';
        }

        $dom = self::load($normalizedHtml);
        $body = $dom->getElementsByTagName('body')->item(0);

        foreach (self::children($body, 'ul') as $list) {
            if ($list->getElementsByTagName('a')->length > 0) {
                continue;
            }

            $rows = array_map(fn (DOMElement $li) => preg_match('/^([^:]{2,30}):\s*(\S.*)$/su', trim($li->textContent), $m) ? [trim($m[1]), trim($m[2])] : null, self::children($list, 'li'));

            if (count($rows) < 3 || in_array(null, $rows, true)) {
                continue;
            }

            $note = strcasecmp(end($rows)[0], 'note') === 0 ? array_pop($rows)[1] : null;
            $html = '<div class="detail-card not-prose"><dl>'
                .implode('', array_map(fn (array $row) => '<div><dt>'.e($row[0]).'</dt><dd>'.e($row[1]).'</dd></div>', $rows))
                .'</dl>'.($note !== null ? '<p class="detail-card__note">'.e($note).'</p>' : '').'</div>';
            self::replaceWithHtml($dom, $list, $html);
        }

        return self::save($body);
    }

    public static function readingMinutes(string $html): int
    {
        return max(1, (int) round(str_word_count(strip_tags($html)) / 220));
    }

    /** Stray "&nbsp;" between blocks, which the old editor left behind as vertical spacing. */
    private static function dropBlankTextNodes(DOMElement $body): void
    {
        foreach (iterator_to_array($body->childNodes) as $node) {
            if ($node->nodeType === XML_TEXT_NODE && str_contains($node->textContent, "\u{00A0}") && self::plainText($node->textContent) === '') {
                $body->removeChild($node);
            }
        }
    }

    private static function plainText(string $text): string
    {
        return mb_strtolower(trim(preg_replace('/[\s\x{00A0}]+/u', ' ', html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8'))));
    }

    private static function splitLineLists(DOMDocument $dom, DOMElement $body): void
    {
        foreach (self::children($body, 'p') as $p) {
            if ($p->getElementsByTagName('img')->length > 0 || ! str_contains($p->textContent, "\n")) {
                continue;
            }

            $lines = array_values(array_filter(array_map('trim', explode("\n", self::innerHtml($p))), fn ($l) => ! self::isBlankOrRule($l)));

            if (count($lines) < 2) {
                continue;
            }

            $lead = array_shift($lines);
            $isList = count($lines) >= 2 && collect($lines)->every(fn ($l) => mb_strlen(strip_tags($l)) <= self::MAX_ITEM_LENGTH);

            if (! $isList) {
                $next = $p->nextElementSibling;
                $after = $next?->tagName === 'p' ? strtok(trim($next->textContent), "\n") : '';
                self::replaceWithHtml($dom, $p, self::proseWithHeadings(array_merge([$lead], $lines), (string) $after));

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

    /**
     * Prose lines from one paragraph: headings fused in by the old editor ("Discover Unique Features")
     * become h2s, back-to-back full sentences become separate paragraphs, and short lines keep their line breaks.
     *
     * @param  array<int, string>  $lines
     * @param  string  $after  First line of the following paragraph, so a trailing heading line can be recognised.
     */
    private static function proseWithHeadings(array $lines, string $after = ''): string
    {
        $isHeading = array_map(fn (string $line, int $i) => self::looksLikeHeading($line) && self::isSentence($lines[$i + 1] ?? $after), $lines, array_keys($lines));

        $html = '';
        $buffer = [];
        $flush = function () use (&$html, &$buffer): void {
            if ($buffer !== []) {
                $html .= '<p>'.implode('<br>', $buffer).'</p>';
                $buffer = [];
            }
        };

        foreach ($lines as $i => $line) {
            if ($isHeading[$i]) {
                $flush();
                $html .= '<h2>'.trim(strip_tags($line, '<em><strong><b><i>')).'</h2>';
            } else {
                if ($buffer !== [] && self::isSentence($line) && self::isSentence(end($buffer))) {
                    $flush();
                }
                $buffer[] = $line;
            }
        }
        $flush();

        return $html;
    }

    /** A short title-case line with no closing punctuation, e.g. "Trekking Packages & Services". */
    private static function looksLikeHeading(string $line): bool
    {
        $text = trim(html_entity_decode(strip_tags($line), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $words = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY);

        if (mb_strlen($text) > 70 || count($words) < 2 || count($words) > 10 || str_contains($text, ':') || ! preg_match('/^\p{Lu}.*[\p{L}\d)]$/u', $text)) {
            return false;
        }

        $significant = array_filter($words, fn (string $word) => mb_strlen($word) > 3);

        return $significant === [] || count(array_filter($significant, fn (string $word) => preg_match('/^[\p{Lu}\d&]/u', $word))) / count($significant) >= 0.6;
    }

    /** A full sentence of body copy, which is what follows a real heading. */
    private static function isSentence(string $line): bool
    {
        $text = trim(html_entity_decode(strip_tags($line), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        return mb_strlen($text) > 60 || preg_match('/[.!?]["”’)]?$/u', $text) === 1;
    }

    /** Empty, &nbsp;-only or "-----" / "_____" separator lines the old editor used as spacing. */
    private static function isBlankOrRule(string $line): bool
    {
        $text = trim(html_entity_decode(strip_tags($line), ENT_QUOTES | ENT_HTML5, 'UTF-8'), " \t\u{00A0}");

        return $text === '' || preg_match('/^[-_=—–]{5,}$/u', $text) === 1;
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
