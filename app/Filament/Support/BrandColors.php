<?php

namespace App\Filament\Support;

/**
 * Hand-tuned OKLCH colour scales for the admin panel.
 *
 * Filament's Color::hex() keeps only the hue of a colour and applies one fixed lightness/chroma curve,
 * which turns the brand's deep Juniper into a bright teal. These scales keep the real brand shades at the
 * steps Filament uses most: 600 = buttons and links in light mode, 400/500 = the same in dark mode.
 */
final class BrandColors
{
    /** Juniper #1f3a33 (≈ 700) — primary buttons, links, focus rings. */
    public const JUNIPER = [
        50 => 'oklch(0.975 0.012 172)',
        100 => 'oklch(0.95 0.022 172)',
        200 => 'oklch(0.9 0.04 172)',
        300 => 'oklch(0.82 0.06 172)',
        400 => 'oklch(0.69 0.07 172)',
        500 => 'oklch(0.56 0.065 172)',
        600 => 'oklch(0.4 0.05 172)',
        700 => 'oklch(0.335 0.042 172)',
        800 => 'oklch(0.29 0.036 172)',
        900 => 'oklch(0.255 0.03 172)',
        950 => 'oklch(0.2 0.022 172)',
    ];

    /** Saffron #d9a441 (≈ 500) — warnings, pending states. */
    public const SAFFRON = [
        50 => 'oklch(0.98 0.02 85)',
        100 => 'oklch(0.955 0.045 85)',
        200 => 'oklch(0.91 0.08 84)',
        300 => 'oklch(0.86 0.11 83)',
        400 => 'oklch(0.8 0.13 82)',
        500 => 'oklch(0.74 0.135 80)',
        600 => 'oklch(0.64 0.125 76)',
        700 => 'oklch(0.54 0.105 72)',
        800 => 'oklch(0.45 0.085 70)',
        900 => 'oklch(0.38 0.07 68)',
        950 => 'oklch(0.27 0.05 66)',
    ];

    /** Terracotta #b5532e (≈ 600) — destructive actions, errors. */
    public const TERRACOTTA = [
        50 => 'oklch(0.97 0.015 40)',
        100 => 'oklch(0.94 0.035 40)',
        200 => 'oklch(0.88 0.07 40)',
        300 => 'oklch(0.8 0.11 41)',
        400 => 'oklch(0.7 0.145 41)',
        500 => 'oklch(0.62 0.155 42)',
        600 => 'oklch(0.56 0.145 42)',
        700 => 'oklch(0.48 0.125 42)',
        800 => 'oklch(0.41 0.1 42)',
        900 => 'oklch(0.35 0.08 42)',
        950 => 'oklch(0.25 0.055 42)',
    ];

    /** Glacier blue #5e8796 (≈ 500) — informational badges and notices. */
    public const GLACIER = [
        50 => 'oklch(0.975 0.01 220)',
        100 => 'oklch(0.95 0.018 220)',
        200 => 'oklch(0.9 0.03 220)',
        300 => 'oklch(0.82 0.045 220)',
        400 => 'oklch(0.72 0.055 220)',
        500 => 'oklch(0.6 0.055 220)',
        600 => 'oklch(0.52 0.055 222)',
        700 => 'oklch(0.44 0.05 224)',
        800 => 'oklch(0.37 0.04 226)',
        900 => 'oklch(0.31 0.035 228)',
        950 => 'oklch(0.22 0.025 230)',
    ];

    /** Neutral: Stone's warm light end (matches the paper canvas) easing into Juniper green at the dark end. */
    public const GRAY = [
        50 => 'oklch(0.985 0.001 106.4)',
        100 => 'oklch(0.97 0.002 106.4)',
        200 => 'oklch(0.923 0.004 80)',
        300 => 'oklch(0.869 0.006 90)',
        400 => 'oklch(0.709 0.012 150)',
        500 => 'oklch(0.553 0.016 160)',
        600 => 'oklch(0.444 0.018 165)',
        700 => 'oklch(0.374 0.022 168)',
        800 => 'oklch(0.315 0.028 170)',
        900 => 'oklch(0.262 0.027 170)',
        950 => 'oklch(0.205 0.022 170)',
    ];
}
