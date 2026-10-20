<?php

namespace App\Services\WordPress;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;
use DOMXPath;
use Illuminate\Support\Str;

/**
 * Reduces Gutenberg / Elementor markup to clean, semantic HTML and rewrites
 * links and images that point at the old WordPress site.
 */
class HtmlCleaner
{
    /** Tags kept as-is (attributes are still filtered). */
    protected const KEEP = [
        'p', 'h2', 'h3', 'h4', 'ul', 'ol', 'li', 'strong', 'em', 'a', 'table', 'thead', 'tbody', 'tfoot',
        'tr', 'th', 'td', 'blockquote', 'img', 'figure', 'figcaption', 'br', 'hr', 'iframe', 'sup', 'sub',
    ];

    /** Tags renamed to a kept equivalent. */
    protected const RENAME = ['h1' => 'h2', 'h5' => 'h4', 'h6' => 'h4', 'b' => 'strong', 'i' => 'em', 'cite' => 'em', 'caption' => 'p'];

    /** Tags removed together with their content. */
    protected const DROP = [
        'script', 'style', 'noscript', 'template', 'svg', 'form', 'input', 'button', 'select', 'textarea',
        'label', 'link', 'meta', 'head', 'title', 'object', 'embed', 'video', 'audio', 'canvas', 'nav', 'dialog',
        'trustindex-image',
    ];

    /** Block containers whose loose inline content gets wrapped in <p>. */
    protected const BLOCKS = ['div', 'section', 'article', 'header', 'footer', 'aside', 'main', 'body', 'center', 'address', 'details', 'summary'];

    protected const INLINE = ['a', 'strong', 'em', 'span', 'b', 'i', 'u', 'small', 'mark', 'abbr', 'code', 'sup', 'sub', 'br', 'img', 'font', 'cite', 'q', 's', 'del', 'ins', 'time'];

    /** Elementor/WTE widgets that are pure UI (carousels of trips, reviews, modals...). */
    protected const DROP_CLASS_PATTERNS = [
        '/(^|\s)(ti-widget|ti-[a-z-]+)(\s|$)/', '/(^|\s)wte-/', '/(^|\s)wpte-/', '/(^|\s)category-trip/',
        '/(^|\s)elementor-widget-(form|posts|archive-posts|nav-menu|search-form|share-buttons|wp-widget|shortcode|loop-grid|loop-carousel|testimonial-carousel|reviews|social-icons|google_maps)(\s|$)/',
        '/(^|\s)ekit-wid-con\s+ekit-team/', '/(^|\s)elementskit-team-modal/', '/(^|\s)ekit_modal/', '/(^|\s)elementskit-post/',
        '/(^|\s)swiper-button/', '/(^|\s)swiper-pagination/', '/(^|\s)screen-reader-text(\s|$)/', '/(^|\s)elementor-icon(\s|$)/',
        '/(^|\s)wp-block-buttons(\s|$)/',
    ];

    protected const EMBED_HOSTS = ['youtube.com', 'youtube-nocookie.com', 'youtu.be', 'vimeo.com', 'player.vimeo.com', 'google.com/maps', 'maps.google.', 'www.google.com/maps'];

    /** @var (callable(string): ?string)|null maps an old image URL to a new one */
    protected $imageResolver = null;

    public function __construct(protected ?UrlMap $urls = null) {}

    public function withUrlMap(?UrlMap $urls): static
    {
        $this->urls = $urls;

        return $this;
    }

    /** @param  (callable(string, ?int): ?string)|null  $resolver */
    public function withImageResolver(?callable $resolver): static
    {
        $this->imageResolver = $resolver;

        return $this;
    }

    public function clean(?string $html, ?string $dropLeadingHeading = null): string
    {
        $html = (string) $html;

        if (trim($html) === '') {
            return '';
        }

        // Gutenberg block comments and any other HTML comments.
        $html = preg_replace('/<!--.*?-->/s', '', $html) ?? $html;

        $dom = $this->load($html);
        $body = $dom->getElementsByTagName('body')->item(0);

        if (! $body) {
            return '';
        }

        $this->dropJunk($dom);
        $this->walk($body);
        $this->wrapLooseInline($body);
        $this->removeEmpty($body);

        if ($dropLeadingHeading !== null) {
            $this->dropLeadingHeading($body, $dropLeadingHeading);
        }

        return $this->serialize($body);
    }

    /** Only rewrite <img>/<a> URLs of already-clean HTML (used after a --skip-media import). */
    public function rewriteUrls(?string $html): string
    {
        $html = (string) $html;

        if (trim($html) === '') {
            return '';
        }

        $dom = $this->load($html);
        $body = $dom->getElementsByTagName('body')->item(0);

        foreach (iterator_to_array($dom->getElementsByTagName('img')) as $img) {
            $this->cleanImage($img);
        }

        foreach (iterator_to_array($dom->getElementsByTagName('a')) as $a) {
            if ($a->hasAttribute('href')) {
                $a->setAttribute('href', $this->rewriteHref($a->getAttribute('href')));
            }
        }

        return $this->serialize($body);
    }

    /** Plain text, whitespace-normalised. */
    public static function text(?string $html): string
    {
        $html = preg_replace('/<!--.*?-->/s', '', (string) $html) ?? '';
        $html = preg_replace('/<(br|\/p|\/li|\/h[1-6]|\/div|\/tr)[^>]*>/i', '$0 ', $html) ?? $html;

        return TitleCleaner::text($html);
    }

    public static function excerpt(?string $html, int $words = 40): string
    {
        $text = self::text($html);

        return $text === '' ? '' : Str::words($text, $words, '…');
    }

    public function load(string $html): DOMDocument
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML(
            '<!DOCTYPE html><html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head><body>'.$html.'</body></html>',
            LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_NONET | LIBXML_COMPACT
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return $dom;
    }

    protected function dropJunk(DOMDocument $dom): void
    {
        $xpath = new DOMXPath($dom);

        foreach (iterator_to_array($xpath->query('//*[@class]') ?: []) as $el) {
            /** @var DOMElement $el */
            if (! $el->parentNode) {
                continue;
            }

            $class = $el->getAttribute('class');

            // Elementor counters render "0" and keep the real number in data-to-value.
            if (str_contains($class, 'elementor-counter-number') && $el->hasAttribute('data-to-value')) {
                $el->textContent = $el->getAttribute('data-to-value');

                continue;
            }

            foreach (self::DROP_CLASS_PATTERNS as $pattern) {
                if (preg_match($pattern, $class)) {
                    $el->parentNode->removeChild($el);
                    break;
                }
            }
        }

        // Elementor hides duplicate mobile/desktop content with these.
        foreach (iterator_to_array($xpath->query('//*[@aria-hidden="true"]') ?: []) as $el) {
            if ($el->parentNode && trim($el->textContent) === '') {
                $el->parentNode->removeChild($el);
            }
        }
    }

    protected function walk(DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof DOMElement) {
                $this->cleanElement($child);
            } elseif ($child->nodeType === XML_COMMENT_NODE || $child->nodeType === XML_PI_NODE) {
                $node->removeChild($child);
            }
        }
    }

    protected function cleanElement(DOMElement $el): void
    {
        $tag = strtolower($el->tagName);

        if (in_array($tag, self::DROP, true)) {
            $el->parentNode?->removeChild($el);

            return;
        }

        if ($tag === 'iframe') {
            $this->cleanIframe($el);

            return;
        }

        if (isset(self::RENAME[$tag])) {
            $el = $this->rename($el, self::RENAME[$tag]);
            $tag = $el->tagName;
        }

        // Recurse first so we unwrap bottom-up.
        $this->walk($el);

        if (in_array($tag, self::BLOCKS, true)) {
            if (! $this->insideTextBlock($el)) {
                $this->wrapLooseInline($el);
            }

            $this->unwrap($el);

            return;
        }

        if (! in_array($tag, self::KEEP, true)) {
            $this->unwrap($el);

            return;
        }

        match ($tag) {
            'img' => $this->cleanImage($el),
            'a' => $this->cleanAnchor($el),
            default => $this->stripAttributes($el, in_array($tag, ['td', 'th'], true) ? ['colspan', 'rowspan'] : []),
        };
    }

    /** True when the element sits inside a paragraph-like/inline element (where <p> is not allowed). */
    protected function insideTextBlock(DOMElement $el): bool
    {
        for ($node = $el->parentNode; $node instanceof DOMElement; $node = $node->parentNode) {
            if (in_array(strtolower($node->tagName), ['p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'li', 'td', 'th', 'a', 'strong', 'em', 'b', 'i', 'span', 'figcaption', 'blockquote'], true)) {
                return true;
            }
        }

        return false;
    }

    protected function cleanIframe(DOMElement $el): void
    {
        $src = $el->getAttribute('src') ?: $el->getAttribute('data-src') ?: $el->getAttribute('data-lazy-src');
        $allowed = $src !== '' && Str::contains(strtolower($src), self::EMBED_HOSTS);

        if (! $allowed) {
            $el->parentNode?->removeChild($el);

            return;
        }

        $this->stripAttributes($el, ['width', 'height', 'title', 'allow', 'allowfullscreen']);
        $el->setAttribute('src', $src);
        $el->setAttribute('loading', 'lazy');

        if (! $el->hasAttribute('allowfullscreen')) {
            $el->setAttribute('allowfullscreen', '');
        }
    }

    protected function cleanImage(DOMElement $el): void
    {
        $src = $el->getAttribute('src');

        foreach (['data-src', 'data-lazy-src', 'data-orig-file', 'data-imgurl'] as $attr) {
            if (($src === '' || str_starts_with($src, 'data:')) && $el->getAttribute($attr) !== '') {
                $src = $el->getAttribute($attr);
            }
        }

        if ($src === '' || str_starts_with($src, 'data:')) {
            $el->parentNode?->removeChild($el);

            return;
        }

        $wpId = preg_match('/wp-image-(\d+)/', $el->getAttribute('class'), $m) ? (int) $m[1] : null;
        $src = html_entity_decode($src);

        if ($this->imageResolver) {
            $src = ($this->imageResolver)($src, $wpId) ?? $src;
        }

        $this->stripAttributes($el, ['alt', 'width', 'height']);
        $el->setAttribute('src', $src);
        $el->setAttribute('alt', trim($el->getAttribute('alt')));
        $el->setAttribute('loading', 'lazy');
    }

    protected function cleanAnchor(DOMElement $el): void
    {
        $href = trim($el->getAttribute('href'));
        $hasContent = trim($el->textContent) !== '' || $el->getElementsByTagName('img')->length > 0;

        if (! $hasContent) {
            $el->parentNode?->removeChild($el);

            return;
        }

        if ($href === '' || $href === '#' || Str::startsWith(strtolower($href), ['javascript:', 'data:'])) {
            $this->unwrap($el);

            return;
        }

        $target = $el->getAttribute('target');
        $rel = $el->getAttribute('rel');
        $this->stripAttributes($el, []);
        $href = $this->rewriteHref($href);
        $el->setAttribute('href', $href);

        if (Str::startsWith($href, ['http://', 'https://'])) {
            $el->setAttribute('target', '_blank');
            $rels = array_unique(array_filter([...explode(' ', $rel), 'noopener']));
            $el->setAttribute('rel', implode(' ', $rels));
        } elseif ($target === '_blank') {
            $el->setAttribute('target', '_blank');
        }
    }

    /** Old-site absolute URL → new relative path; other URLs untouched. */
    public function rewriteHref(string $href): string
    {
        $href = html_entity_decode(trim($href));
        $parts = parse_url($href);

        if ($parts === false) {
            return $href;
        }

        $host = strtolower($parts['host'] ?? '');
        $isOld = $host === '' ? str_starts_with($href, '/') : $this->isOldHost($host);

        if (! $isOld) {
            return $href;
        }

        $path = $parts['path'] ?? '/';

        // Uploaded files: point to the mirrored copy when we have one.
        if (str_contains($path, '/wp-content/')) {
            if ($this->imageResolver && preg_match('/\.(jpe?g|png|gif|webp|avif|svg)$/i', $path)) {
                return ($this->imageResolver)($href, null) ?? $href;
            }

            return $href;
        }

        $new = $this->urls?->resolve($path) ?? UrlMap::normalize($path);
        $query = isset($parts['query']) && ! str_contains($new, '?') ? '?'.$parts['query'] : '';
        $fragment = isset($parts['fragment']) ? '#'.$parts['fragment'] : '';

        return $new.$query.$fragment;
    }

    protected function isOldHost(string $host): bool
    {
        $old = strtolower((string) parse_url((string) config('services.wordpress.url', 'https://wonderhimalaya.com'), PHP_URL_HOST));
        $host = preg_replace('/^www\./', '', $host);

        return $host === preg_replace('/^www\./', '', $old);
    }

    /** @param  array<int, string>  $keep */
    protected function stripAttributes(DOMElement $el, array $keep): void
    {
        foreach (iterator_to_array($el->attributes) as $attr) {
            if (! in_array($attr->name, $keep, true)) {
                $el->removeAttribute($attr->name);
            }
        }
    }

    protected function rename(DOMElement $el, string $tag): DOMElement
    {
        $new = $el->ownerDocument->createElement($tag);

        foreach (iterator_to_array($el->attributes) as $attr) {
            $new->setAttribute($attr->name, $attr->value);
        }

        while ($el->firstChild) {
            $new->appendChild($el->firstChild);
        }

        $el->parentNode->replaceChild($new, $el);

        return $new;
    }

    protected function unwrap(DOMElement $el): void
    {
        $parent = $el->parentNode;

        if (! $parent) {
            return;
        }

        while ($el->firstChild) {
            $parent->insertBefore($el->firstChild, $el);
        }

        $parent->removeChild($el);
    }

    /** Wrap runs of text/inline nodes that sit directly in a block container into <p>. */
    protected function wrapLooseInline(DOMNode $container): void
    {
        $run = [];
        $flush = function () use (&$run, $container) {
            $text = '';

            foreach ($run as $n) {
                $text .= $n->textContent;
            }

            $hasMedia = array_filter($run, fn ($n) => $n instanceof DOMElement && ($n->tagName === 'img' || $n->getElementsByTagName('img')->length));

            if (trim(str_replace("\u{00A0}", ' ', $text)) === '' && ! $hasMedia) {
                $run = [];

                return;
            }

            $p = $container->ownerDocument->createElement('p');
            $container->insertBefore($p, $run[0]);

            foreach ($run as $n) {
                $p->appendChild($n);
            }

            $run = [];
        };

        foreach (iterator_to_array($container->childNodes) as $child) {
            $inline = $child instanceof DOMText
                || ($child instanceof DOMElement && in_array(strtolower($child->tagName), self::INLINE, true));

            if ($inline) {
                $run[] = $child;
            } elseif ($run) {
                $flush();
            }
        }

        if ($run) {
            $flush();
        }
    }

    protected function removeEmpty(DOMNode $root): void
    {
        $xpath = new DOMXPath($root->ownerDocument);
        $tags = ['p', 'h2', 'h3', 'h4', 'li', 'ul', 'ol', 'strong', 'em', 'blockquote', 'figure', 'figcaption', 'a', 'td', 'th', 'tr', 'tbody', 'thead', 'table', 'sup', 'sub'];

        do {
            $removed = 0;

            foreach (iterator_to_array($xpath->query('.//*', $root) ?: []) as $el) {
                /** @var DOMElement $el */
                if (! $el->parentNode || ! in_array($el->tagName, $tags, true)) {
                    continue;
                }

                // Table cells may legitimately be empty while the row has content.
                if (in_array($el->tagName, ['td', 'th'], true)) {
                    continue;
                }

                $text = trim(str_replace("\u{00A0}", ' ', $el->textContent));
                $media = $xpath->query('.//img|.//iframe', $el)->length;

                if ($text === '' && $media === 0) {
                    $el->parentNode->removeChild($el);
                    $removed++;
                }
            }
        } while ($removed > 0);

        // Leading/trailing <br> inside paragraphs and double <br>s.
        foreach (iterator_to_array($root->getElementsByTagName('br')) as $br) {
            $parent = $br->parentNode;

            if (! $parent instanceof DOMElement) {
                continue;
            }

            $prev = $this->siblingSkippingSpace($br, 'previousSibling');
            $next = $this->siblingSkippingSpace($br, 'nextSibling');

            if ($prev === null || $next === null || ($next instanceof DOMElement && $next->tagName === 'br') || $parent->tagName === 'body') {
                $parent->removeChild($br);
            }
        }
    }

    protected function siblingSkippingSpace(DOMNode $node, string $direction): ?DOMNode
    {
        $sibling = $node->{$direction};

        while ($sibling instanceof DOMText && trim(str_replace("\u{00A0}", ' ', $sibling->textContent)) === '') {
            $sibling = $sibling->{$direction};
        }

        return $sibling;
    }

    protected function dropLeadingHeading(DOMNode $body, string $title): void
    {
        foreach ($body->childNodes as $child) {
            if ($child instanceof DOMText && trim($child->textContent) === '') {
                continue;
            }

            if ($child instanceof DOMElement && in_array($child->tagName, ['h2', 'h3'], true)
                && Str::lower(TitleCleaner::text($child->textContent)) === Str::lower(TitleCleaner::text($title))) {
                $body->removeChild($child);
            }

            return;
        }
    }

    protected function serialize(DOMNode $body): string
    {
        $html = '';

        foreach ($body->childNodes as $child) {
            $html .= $body->ownerDocument->saveHTML($child);
        }

        $html = str_replace("\u{00A0}", '&nbsp;', $html);
        $html = preg_replace('/[ \t]*\n\s*/', "\n", $html) ?? $html;
        $html = preg_replace('/[ \t]{2,}/', ' ', $html) ?? $html;
        // Put block elements on their own lines for readable diffs.
        $html = preg_replace('/\s*(<\/?(?:p|h[2-4]|ul|ol|li|table|thead|tbody|tr|blockquote|figure)\b[^>]*>)\s*/i', '$1', $html) ?? $html;
        $html = preg_replace('/(<\/(?:p|h[2-4]|ul|ol|li|table|thead|tbody|tr|blockquote|figure)>)/i', "$1\n", $html) ?? $html;
        $html = preg_replace('/(<(?:ul|ol|table|thead|tbody|tr|blockquote|figure)\b[^>]*>)/i', "$1\n", $html) ?? $html;
        $html = preg_replace('/(&nbsp;\s*)+(<\/(?:p|li|h[2-4])>)/i', '$2', $html) ?? $html;
        $html = preg_replace('/(<(?:p|li|h[2-4])>)(\s*&nbsp;)+/i', '$1', $html) ?? $html;

        return trim($html);
    }
}
