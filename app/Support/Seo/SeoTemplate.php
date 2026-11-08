<?php

namespace App\Support\Seo;

/**
 * Renders Yoast-style title/description templates: "%%title%% %%sep%% %%sitename%%".
 * Empty variables leave no dangling separators or double spaces behind.
 */
final class SeoTemplate
{
    /** @param  array<string, string|int|float|null>  $variables  keys without the %% markers */
    public static function render(?string $template, array $variables): string
    {
        if ($template === null || trim($template) === '') {
            return '';
        }

        $separator = SeoSettings::separator();
        $variables += ['sep' => $separator, 'sitename' => SeoSettings::siteName(), 'year' => now()->year];

        $text = preg_replace_callback('/%%([a-z_]+)%%/', fn (array $m): string => $m[1] === 'sep'
            ? "\u{0}"
            : trim((string) ($variables[$m[1]] ?? '')), $template);

        // A separator swallows neighbouring separators and dangling punctuation ("Name — | Site" → "Name | Site").
        $text = preg_replace('/[\s—–\-:,]*\x{0}[\s\x{0}—–\-:,]*/u', "\u{0}", $text);
        $text = str_replace("\u{0}", " {$separator} ", trim($text, "\u{0} \t\n"));

        return preg_replace(['/\s+/u', '/\s+([,.:;!?])/u', '/^[\s—–\-:,]+|[\s—–\-:,]+$/u'], [' ', '$1', ''], $text);
    }
}
