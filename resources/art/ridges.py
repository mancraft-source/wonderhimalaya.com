"""Generates resources/views/components/site/ridge.blade.php — organic section dividers (original SVG artwork).

Usage: python3 resources/art/ridges.py resources/views/components/site/ridge.blade.php
"""
import math
import random
import sys

if len(sys.argv) < 2 or sys.argv[1] in ('-h', '--help'):
    print(__doc__.strip())
    sys.exit(0)

W, H = 1600, 160


def ridge(seed, base, bumps, rough, step=8):
    rnd = random.Random(seed)
    pts = []
    for x in range(0, W + step, step):
        y = base
        for (px, ph, pw, sharp) in bumps:
            d = abs(x - px) / pw
            if d < 1:
                y -= ph * ((1 - d) ** sharp if sharp else 0.5 + 0.5 * math.cos(math.pi * d))
        y += math.sin(x / 29 + seed) * rough + math.sin(x / 9.7 + seed * 3) * rough * 0.4 + rnd.uniform(-rough * 0.3, rough * 0.3)
        pts.append((x, round(min(H, y), 1)))
    return pts


def area(pts):
    return 'M' + ' L'.join(f'{x},{y}' for x, y in pts) + f' L{W},{H} L0,{H} Z'


def at(pts, x):
    return min(pts, key=lambda p: abs(p[0] - x))[1]


def snowcaps(pts, line, seed):
    rnd = random.Random(seed)
    caps, cur, out = [], [], []
    for p in pts:
        if p[1] < line:
            cur.append(p)
        elif cur:
            caps.append(cur)
            cur = []
    for c in caps:
        if len(c) < 3:
            continue
        top = min(y for _, y in c)
        lower = [(x, round(max(y + 2, line - (line - top) * rnd.uniform(0.0, 0.5)), 1)) for x, y in reversed(c)]
        out.append('M' + ' L'.join(f'{x},{y}' for x, y in c + lower) + ' Z')
    return ' '.join(out)


def pines(pts, seed, every=(8, 22), size=(14, 30)):
    rnd = random.Random(seed)
    out, x = [], 6
    while x < W:
        gy = at(pts, x)
        h = rnd.uniform(*size)
        w = h * rnd.uniform(0.34, 0.42)
        out.append(f'M{x:.1f},{gy - h:.1f} L{x + w / 2:.1f},{gy + 2:.1f} L{x - w / 2:.1f},{gy + 2:.1f} Z')
        x += rnd.uniform(*every) if rnd.random() > 0.22 else rnd.uniform(60, 160)
    return ' '.join(out)


def terraces(pts, rows=5):
    lines = []
    for r in range(1, rows + 1):
        seg = [(x, round(y + r * 9 + math.sin(x / 70 + r) * 2, 1)) for x, y in pts if y + r * 9 < H - 4]
        if len(seg) > 2:
            lines.append('M' + ' L'.join(f'{x},{y}' for x, y in seg))
    return ' '.join(lines)


variants = {}

back = ridge(3, 120, [(180, 70, 170, 1.4), (520, 92, 190, 1.3), (900, 80, 160, 1.4), (1260, 96, 200, 1.3), (1520, 60, 150, 1.4)], 3)
front = ridge(7, 150, [(320, 70, 170, 1.5), (720, 58, 150, 1.5), (1100, 76, 190, 1.5), (1430, 48, 160, 1.5)], 2.5)
variants['peaks'] = {'back': area(back), 'front': area(front), 'extra': ''}

back = ridge(11, 128, [(240, 92, 180, 1.35), (640, 112, 200, 1.3), (1040, 98, 180, 1.35), (1400, 108, 190, 1.3)], 3)
front = ridge(13, 156, [(120, 52, 170, 1.5), (480, 66, 190, 1.45), (860, 74, 180, 1.45), (1240, 60, 200, 1.5), (1560, 44, 140, 1.5)], 2.5)
variants['snow'] = {'back': area(back), 'front': area(front), 'extra': snowcaps(front, 112, 5), 'extra_back': snowcaps(back, 62, 9)}

back = ridge(17, 112, [(300, 30, 360, 0), (900, 36, 380, 0), (1400, 26, 300, 0)], 3)
front = ridge(19, 140, [(140, 26, 300, 0), (700, 30, 360, 0), (1250, 34, 340, 0)], 2)
variants['pines'] = {'back': area(back), 'front': area(front), 'extra': pines(front, 23, (8, 20), (18, 40)), 'extra_back': pines(back, 29, (14, 34), (10, 20))}

back = ridge(31, 110, [(200, 40, 320, 0), (760, 52, 380, 0), (1300, 44, 340, 0)], 2)
front = ridge(37, 138, [(460, 46, 400, 0), (1060, 40, 420, 0), (1560, 30, 260, 0)], 1.5)
variants['hills'] = {'back': area(back), 'front': area(front), 'extra': ''}

back = ridge(41, 116, [(260, 48, 330, 0), (880, 60, 360, 0), (1440, 50, 300, 0)], 2)
front = ridge(43, 132, [(520, 58, 420, 0), (1160, 64, 420, 0)], 1.2)
variants['terraces'] = {'back': area(back), 'front': area(front), 'extra': '', 'lines': terraces(front)}


def render(name, v):
    eb = v.get('extra_back', '')
    out = [f"    @case('{name}')"]
    out.append(f'        <div class="absolute inset-0 opacity-50" data-parallax="0.05"><svg viewBox="0 0 {W} {H}" preserveAspectRatio="none" class="absolute inset-x-0 bottom-0 h-full w-full"><path d="{v["back"]}" fill="{{{{ $back }}}}"/>' + (f'<path d="{eb}" fill="{{{{ $snow }}}}" opacity=".6"/>' if eb and name == 'snow' else (f'<path d="{eb}" fill="{{{{ $back }}}}"/>' if eb else '')) + '</svg></div>')
    front = f'<path d="{v["front"]}" fill="{{{{ $to }}}}"/>'
    if name == 'snow':
        front += f'<path d="{v["extra"]}" fill="{{{{ $snow }}}}"/>'
    elif v['extra']:
        front += f'<path d="{v["extra"]}" fill="{{{{ $to }}}}"/>'
    if v.get('lines'):
        front += f'<path d="{v["lines"]}" fill="none" stroke="{{{{ $lines }}}}" stroke-width="1.2" stroke-linecap="round" opacity=".55"/>'
    out.append(f'        <svg viewBox="0 0 {W} {H}" preserveAspectRatio="none" class="absolute inset-x-0 bottom-0 h-full w-full">{front}</svg>')
    out.append('        @break')
    return '\n'.join(out)


blade = """{{-- Organic section divider — original SVG mountain artwork, no licence needed.
     Generated by resources/art/ridges.py. Place between two sections:
       <x-site.ridge variant="pines" from="#f4efe6" to="#eee8dc" />
     `from` = colour of the section above (null when overlaying a photo), `to` = colour of the section below.
     The back layer drifts with scroll (parallax); the front layer stays aligned with the next section. --}}
@props(['variant' => 'peaks', 'from' => null, 'to' => '#f4efe6', 'back' => null, 'snow' => '#fbf9f5', 'lines' => null, 'overlay' => false, 'size' => 'md'])
@php
    $back ??= $to;
    $lines ??= $to;
@endphp
<div aria-hidden="true" {{ $attributes->class([
    'pointer-events-none overflow-hidden',
    'h-16 sm:h-24 lg:h-32' => $size === 'md',
    'h-24 sm:h-32 lg:h-44' => $size === 'lg',
    'h-28 sm:h-40 lg:h-60' => $size === 'xl',
    'absolute inset-x-0 bottom-0 z-10' => $overlay,
    'relative -mb-px' => ! $overlay,
]) }} @if ($from) style="background: {{ $from }}" @endif>
@switch($variant)
""" + '\n'.join(render(n, v) for n, v in variants.items()) + """
@endswitch
</div>
"""

open(sys.argv[1], 'w').write(blade)
print(f'Wrote {sys.argv[1]} ({len(blade)} bytes, variants: {", ".join(variants)})')
