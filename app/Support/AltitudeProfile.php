<?php

namespace App\Support;

use Illuminate\Support\Collection;

/** Builds SVG path data for the itinerary altitude chart. */
final class AltitudeProfile
{
    public const W = 1000;

    public const H = 240;

    /**
     * @param  Collection<int, array{day: int, alt: int, title: string}>  $points
     * @return array{line: string, area: string, dots: array<int, array{x: float, y: float, day: int, alt: int, title: string}>, ticks: array<int, array{y: float, label: string}>, max: int}|null
     */
    public static function make(Collection $points): ?array
    {
        if ($points->count() < 3) {
            return null;
        }

        $min = max(0, (int) floor(($points->min('alt') - 400) / 500) * 500);
        $max = (int) ceil(($points->max('alt') + 300) / 500) * 500;
        $pad = 18;
        $n = $points->count();

        $dots = $points->values()->map(function ($p, $i) use ($n, $min, $max, $pad) {
            $x = $pad + ($i / max(1, $n - 1)) * (self::W - 2 * $pad);
            $y = $pad + (1 - ($p['alt'] - $min) / max(1, $max - $min)) * (self::H - 2 * $pad);

            return ['x' => round($x, 1), 'y' => round($y, 1)] + $p;
        });

        // Smooth line using Catmull-Rom → cubic Bézier.
        $d = 'M'.$dots[0]['x'].','.$dots[0]['y'];
        for ($i = 0; $i < $n - 1; $i++) {
            $p0 = $dots[max(0, $i - 1)];
            $p1 = $dots[$i];
            $p2 = $dots[$i + 1];
            $p3 = $dots[min($n - 1, $i + 2)];
            $c1x = $p1['x'] + ($p2['x'] - $p0['x']) / 6;
            $c1y = $p1['y'] + ($p2['y'] - $p0['y']) / 6;
            $c2x = $p2['x'] - ($p3['x'] - $p1['x']) / 6;
            $c2y = $p2['y'] - ($p3['y'] - $p1['y']) / 6;
            $d .= sprintf(' C%.1f,%.1f %.1f,%.1f %.1f,%.1f', $c1x, $c1y, $c2x, $c2y, $p2['x'], $p2['y']);
        }

        $area = $d.' L'.$dots[$n - 1]['x'].','.self::H.' L'.$dots[0]['x'].','.self::H.' Z';

        $ticks = collect(range($min, $max, max(500, (int) (ceil(($max - $min) / 4 / 500) * 500))))
            ->map(fn ($v) => ['y' => round($pad + (1 - ($v - $min) / max(1, $max - $min)) * (self::H - 2 * $pad), 1), 'label' => number_format($v).' m'])
            ->all();

        return ['line' => $d, 'area' => $area, 'dots' => $dots->all(), 'ticks' => $ticks, 'max' => (int) $points->max('alt')];
    }
}
