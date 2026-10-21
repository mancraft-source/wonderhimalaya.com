"""Builds the 'Journey across Nepal' footer panorama (original SVG artwork) as a Blade component."""
import math
import random
import sys

if len(sys.argv) < 2 or sys.argv[1] in ('-h', '--help'):
    print('Usage: python3 nepal.py OUTPUT.blade.php\n\nGenerates the "Journey across Nepal" footer panorama (original SVG artwork: Annapurna & Machhapuchhre, '
          'Phewa Lake/Pokhara, Kathmandu pagoda, Mt. Everest with summit plume, trekkers, Lumbini stupa) as a Blade component.')
    sys.exit(0)

W, H = 1600, 400
OUT = sys.argv[1]


def ridge(seed, base, bumps, rough, step=8):
    rnd = random.Random(seed)
    pts = []
    for x in range(0, W + step, step):
        y = base
        for (px, ph, pw) in bumps:
            d = abs(x - px) / pw
            if d < 1:
                y -= ph * (0.5 + 0.5 * math.cos(math.pi * d))
        y += math.sin(x / 31 + seed) * rough + math.sin(x / 11 + seed * 2) * rough * 0.4 + rnd.uniform(-rough * 0.3, rough * 0.3)
        pts.append((x, round(y, 1)))
    return pts


def area(pts):
    return 'M' + ' L'.join(f'{x},{y}' for x, y in pts) + f' L{W},{H} L0,{H} Z'


def at(pts, x):
    return min(pts, key=lambda p: abs(p[0] - x))[1]


def poly(points):
    return 'M' + ' L'.join(f'{x},{y}' for x, y in points) + ' Z'


def flags(x1, y1, x2, y2, sag, n, size=1.0):
    cols = ['#3f7cc4', '#fbf9f5', '#c0392b', '#2f8f5b', '#e0b04a']
    line, out = [], []
    for i in range(n + 1):
        t = i / n
        x = x1 + (x2 - x1) * t
        y = y1 + (y2 - y1) * t + math.sin(math.pi * t) * sag
        line.append((round(x, 1), round(y, 1)))
    for i, (x, y) in enumerate(line[1:-1], 1):
        w, h = 7 * size, 10 * size
        out.append(f'<path d="M{x},{y} l{-w:.1f},{1.4*size:.1f} l{2*size:.1f},{h:.1f} l{w:.1f},{-1.4*size:.1f} Z" fill="{cols[i % 5]}"/>')
    return f'<path d="M' + ' L'.join(f'{x},{y}' for x, y in line) + '" fill="none" stroke="#fbf9f5" stroke-opacity=".75" stroke-width="1.3"/>' + ''.join(out)


# ------------------------------------------------------------------ FAR: the high Himalaya
far_base = ridge(2, 262, [(140, 18, 160), (560, 22, 180), (1180, 20, 220), (1480, 16, 200)], 3)
annapurna = [(40, 262), (95, 205), (140, 168), (176, 150), (205, 146), (232, 156), (256, 145), (282, 176), (302, 158), (318, 128), (330, 96), (337, 110), (347, 101), (354, 114), (371, 152), (398, 182), (448, 204), (520, 262)]
annapurna_snow = poly([(140, 168), (176, 150), (205, 146), (232, 156), (256, 145), (266, 156), (246, 167), (224, 160), (203, 171), (180, 163), (158, 175)])
fishtail_snow = poly([(318, 128), (330, 96), (337, 110), (347, 101), (354, 114), (363, 136), (350, 129), (342, 140), (333, 126), (324, 138)])
fishtail_shadow = poly([(337, 110), (347, 101), (354, 114), (371, 152), (398, 182), (448, 204), (520, 262), (345, 262), (341, 150)])

everest = [(588, 262), (640, 214), (688, 196), (722, 162), (752, 136), (782, 84), (800, 48), (813, 66), (838, 100), (866, 116), (892, 92), (910, 106), (946, 142), (990, 154), (1032, 192), (1090, 262)]
everest_shadow = poly([(800, 48), (813, 66), (838, 100), (866, 116), (892, 92), (910, 106), (946, 142), (990, 154), (1032, 192), (1090, 262), (822, 262), (806, 160)])
everest_snow = poly([(752, 136), (782, 84), (800, 48), (813, 66), (838, 100), (826, 106), (813, 94), (804, 118), (790, 104), (776, 132), (764, 128)])
lhotse_snow = poly([(866, 116), (892, 92), (910, 106), (900, 119), (889, 110), (878, 124)])
plume = 'M803,50 C842,34 905,40 975,30 C940,46 892,50 848,56 C830,58 815,58 806,56 Z'

east = [(1060, 262), (1122, 214), (1170, 182), (1206, 170), (1240, 188), (1292, 172), (1330, 192), (1392, 182), (1452, 206), (1530, 214), (1600, 222), (1600, 262)]
east_snow = poly([(1170, 182), (1206, 170), (1240, 188), (1226, 192), (1208, 184), (1190, 194)]) + ' ' + poly([(1292, 172), (1330, 192), (1314, 194), (1300, 186), (1286, 190)])

# ------------------------------------------------------------------ MID: middle hills, Phewa lake, Kathmandu pagoda
mid = ridge(13, 312, [(110, 46, 170), (420, 30, 200), (640, 58, 170), (930, 40, 210), (1240, 30, 200), (1500, 52, 190)], 4)
pagoda_x = 640
py = at(mid, pagoda_x) + 4
pagoda = (
    f'<g fill="#1f3a33">'
    f'<rect x="{pagoda_x - 13}" y="{py - 12}" width="26" height="12"/>'
    f'<path d="M{pagoda_x - 22},{py - 12} L{pagoda_x + 22},{py - 12} L{pagoda_x + 13},{py - 21} L{pagoda_x - 13},{py - 21} Z"/>'
    f'<rect x="{pagoda_x - 9}" y="{py - 29}" width="18" height="8"/>'
    f'<path d="M{pagoda_x - 17},{py - 29} L{pagoda_x + 17},{py - 29} L{pagoda_x + 9},{py - 37} L{pagoda_x - 9},{py - 37} Z"/>'
    f'<rect x="{pagoda_x - 6}" y="{py - 43}" width="12" height="6"/>'
    f'<path d="M{pagoda_x - 12},{py - 43} L{pagoda_x + 12},{py - 43} L{pagoda_x},{py - 54} Z"/>'
    f'<path d="M{pagoda_x},{py - 54} l0,-7" stroke="#e0b04a" stroke-width="2"/>'
    f'</g>'
)

# ------------------------------------------------------------------ FRONT: hills, Lumbini stupa, trekkers, pines
front = ridge(31, 352, [(170, 20, 220), (560, 24, 240), (1240, 52, 230), (1520, 26, 160)], 2.5, step=10)
sx = 1240
sy = at(front, sx) + 2
stupa = (
    f'<g>'
    f'<rect x="{sx - 44}" y="{sy - 8}" width="88" height="8" fill="#e9e1d3"/>'
    f'<rect x="{sx - 36}" y="{sy - 14}" width="72" height="6" fill="#f4efe6"/>'
    f'<path d="M{sx - 32},{sy - 14} A32,30 0 0 1 {sx + 32},{sy - 14} Z" fill="#fbf9f5"/>'
    f'<rect x="{sx - 9}" y="{sy - 56}" width="18" height="13" fill="#e0b04a"/>'
    f'<path d="M{sx - 5},{sy - 51} q2,-2 4,0 M{sx + 1},{sy - 51} q2,-2 4,0" fill="none" stroke="#1f3a33" stroke-width="1.4" stroke-linecap="round"/>'
    f'<path d="M{sx - 1},{sy - 48} q1,3 2,0" fill="none" stroke="#1f3a33" stroke-width="1.2"/>'
    f'<path d="M{sx - 8},{sy - 56} L{sx + 8},{sy - 56} L{sx},{sy - 96} Z" fill="#d9a441"/>'
    f'<path d="M{sx - 6},{sy - 66} L{sx + 6},{sy - 66} M{sx - 4.5},{sy - 74} L{sx + 4.5},{sy - 74} M{sx - 3},{sy - 82} L{sx + 3},{sy - 82}" stroke="#b5862f" stroke-width="1.4"/>'
    f'<circle cx="{sx}" cy="{sy - 99}" r="3" fill="#e0b04a"/>'
    f'</g>'
)
stupa_flags = flags(sx, sy - 96, sx - 150, at(front, sx - 150) - 2, 18, 14, 0.9) + flags(sx, sy - 96, sx + 150, at(front, sx + 150) - 2, 18, 14, 0.9)

trail = []
for x in range(880, 1120, 6):
    trail.append((x, at(front, x) + 6))
trail_path = 'M' + ' L'.join(f'{x},{y}' for x, y in trail)


def trekker(x, h, flip=False):
    y = at(front, x) + 3
    s = -1 if flip else 1
    return (
        f'<g fill="#0f1d19" transform="translate({x},{y}) scale({s * h / 26:.3f},{h / 26:.3f})">'
        '<circle cx="0" cy="-23.5" r="2.6"/>'
        '<path d="M-2.5,-20.5 L2.2,-20.5 L3,-10 L-2,-10 Z"/>'
        '<path d="M-2.4,-20 L-6.8,-19 L-7.4,-11 L-2.6,-11 Z"/>'
        '<path d="M-1.6,-10.5 L-4.8,0 L-2.9,0 L0,-8 L2.6,0 L4.5,0 L1.6,-10.5 Z"/>'
        '<path d="M2.8,-17 L7.4,-1" stroke="#0f1d19" stroke-width="0.9"/>'
        '</g>'
    )


trekkers = trekker(960, 22, flip=True) + trekker(990, 20, flip=True) + trekker(1016, 21, flip=True)

rnd = random.Random(5)
pines = []
x = 8
while x < W:
    skip = 1110 <= x <= 1390 or 930 <= x <= 1030
    if not skip:
        gy = at(front, x)
        h = rnd.uniform(15, 32)
        w = h * rnd.uniform(0.34, 0.42)
        pines.append(f'M{x:.1f},{gy - h:.1f} L{x + w / 2:.1f},{gy + 2:.1f} L{x - w / 2:.1f},{gy + 2:.1f} Z')
    x += rnd.uniform(9, 26) if rnd.random() > 0.3 else rnd.uniform(70, 150)

# Phewa lake (in the mid layer foreground) with a boat
lake = (
    '<path d="M170,330 C230,316 330,314 470,322 C430,336 300,340 190,338 Z" fill="#9db8c2"/>'
    '<path d="M215,328 h48 M300,331 h70 M245,334 h36" stroke="#fbf9f5" stroke-opacity=".7" stroke-width="1.4" stroke-linecap="round"/>'
    '<path d="M332,326 l18,0 l-4,4 l-10,0 Z" fill="#c0392b"/><path d="M341,326 l0,-9" stroke="#1f3a33" stroke-width="1"/>'
)

paraglider = (
    '<g transform="translate(430,128)">'
    '<path d="M-22,0 C-14,-12 14,-12 22,0 C14,-6 -14,-6 -22,0 Z" fill="#b5532e"/>'
    '<path d="M-20,0 L0,26 M20,0 L0,26 M-7,-4 L0,26 M7,-4 L0,26" stroke="#1f3a33" stroke-width=".7" stroke-opacity=".7"/>'
    '<circle cx="0" cy="28" r="2.6" fill="#1f3a33"/>'
    '</g>'
)

plane = (
    '<path d="M150,118 C230,92 330,74 452,62" fill="none" stroke="#1f3a33" stroke-opacity=".35" stroke-width="1.4" stroke-dasharray="4 6"/>'
    '<g transform="translate(460,61) rotate(-6)" fill="#1f3a33" opacity=".8">'
    '<path d="M-10,0 L10,-1 L13,0 L10,1 Z"/><path d="M-1,0 L-6,-7 L-3,-7 L4,0 Z"/><path d="M-1,0 L-6,7 L-3,7 L4,0 Z"/><path d="M-9,0 L-12,-4 L-10,-4 L-7,0 Z"/>'
    '</g>'
)

birds = (
    '<g fill="none" stroke="#27463e" stroke-width="2" stroke-linecap="round" opacity=".5">'
    '<path d="M1080,60 q7,-7 14,0 q7,-7 14,0"/><path d="M1118,80 q5,-5 10,0 q5,-5 10,0"/><path d="M1056,90 q5,-5 10,0 q5,-5 10,0"/>'
    '</g>'
)


def label(x, y, text, anchor='middle', lx=None, ly=None):
    lead = f'<path d="M{x},{y + 5} L{lx},{ly}" stroke="#1f3a33" stroke-opacity=".35" stroke-width="1" stroke-dasharray="2 3"/>' if lx is not None else ''
    return f'<g class="mtn-label">{lead}<text x="{x}" y="{y}" text-anchor="{anchor}" font-size="13" letter-spacing="2.5" fill="#1f3a33" fill-opacity=".62" font-family="var(--font-jetbrains-mono), ui-monospace, monospace">{text}</text></g>'


svg_open = '<svg viewBox="0 0 1600 400" preserveAspectRatio="xMidYMax slice" class="absolute inset-x-0 bottom-0 h-full w-full" aria-hidden="true">'

blade = f'''{{{{-- "Journey across Nepal" panorama between page content and footer. Original SVG artwork — no third-party licence.
     Left → right: Annapurna & Machhapuchhre over Phewa Lake (Pokhara), a Kathmandu pagoda, Mt. Everest with its summit plume,
     trekkers on the trail, and the Lumbini stupa. Layers parallax at different speeds; the front layer matches the footer. --}}}}
<div {{{{ $attributes->class('pointer-events-none relative h-[170px] overflow-hidden sm:h-[240px] lg:h-[340px]') }}}} aria-hidden="true">
    {{{{-- Sky: sun, plane, birds --}}}}
    <div class="absolute top-[16%] left-[61%] size-28 rounded-full bg-saffron-300/45 blur-2xl sm:size-44" data-parallax="0.1"></div>
    <div class="absolute top-[24%] left-[62.5%] size-9 rounded-full bg-saffron-300/80 sm:size-14" data-parallax="0.1"></div>
    <div class="absolute inset-x-0 bottom-0 h-full" data-parallax="0.12">
        {svg_open}{plane}</svg>
    </div>
    {svg_open.replace('class="absolute', 'class="mountain-birds absolute')}{birds}</svg>

    {{{{-- High Himalaya: Annapurna & Fishtail, Everest & Lhotse, eastern peaks --}}}}
    <div class="absolute inset-x-0 bottom-0 h-full" data-parallax="0.07">
        {svg_open}
            <defs>
                <linearGradient id="nepal-far" x1="0" x2="0" y1="0" y2="1"><stop offset="0" stop-color="#a9c0c8"/><stop offset="1" stop-color="#d3dfe2"/></linearGradient>
                <filter id="nepal-plume" x="-20%" y="-50%" width="140%" height="200%"><feGaussianBlur stdDeviation="3.5"/></filter>
            </defs>
            <path d="{area(far_base)}" fill="url(#nepal-far)" opacity=".7"/>
            <path d="{poly(annapurna)}" fill="url(#nepal-far)"/>
            <path d="{fishtail_shadow}" fill="#8fa8b1" opacity=".45"/>
            <path d="{annapurna_snow}" fill="#fbf9f5"/>
            <path d="{fishtail_snow}" fill="#fbf9f5"/>
            <path d="{poly(east)}" fill="url(#nepal-far)" opacity=".85"/>
            <path d="{east_snow}" fill="#fbf9f5" opacity=".9"/>
            <path d="{poly(everest)}" fill="url(#nepal-far)"/>
            <path d="{everest_shadow}" fill="#7f99a3" opacity=".5"/>
            <path d="{everest_snow}" fill="#fbf9f5"/>
            <path d="{lhotse_snow}" fill="#fbf9f5"/>
            <path d="{plume}" fill="#ffffff" opacity=".85" filter="url(#nepal-plume)"/>
            {label(712, 132, 'MT. EVEREST · 8,849 M', 'end', 784, 92)}
            {label(220, 128, 'ANNAPURNA', 'middle')}
            {label(392, 84, 'MACHHAPUCHHRE', 'start', 352, 100)}
        </svg>
    </div>

    {{{{-- Middle hills: paraglider over Phewa Lake (Pokhara), Kathmandu pagoda --}}}}
    <div class="absolute inset-x-0 bottom-0 h-full" data-parallax="0.035">
        {svg_open}
            <path d="{area(mid)}" fill="#3f6b5e"/>
            {lake}
            {pagoda}
            {paraglider}
            {label(320, 360, 'PHEWA LAKE · POKHARA')}
            {label(pagoda_x, py - 70, 'KATHMANDU')}
        </svg>
    </div>

    {{{{-- Front: trekkers on the trail, Lumbini stupa with prayer flags, pines (footer colour at the base) --}}}}
    {svg_open}
        <path d="{area(front)}" fill="#27463e"/>
        <path d="{trail_path}" fill="none" stroke="#e9e1d3" stroke-opacity=".55" stroke-width="2" stroke-dasharray="3 6" stroke-linecap="round"/>
        {trekkers}
        {stupa_flags}
        {stupa}
        <path d="{' '.join(pines)}" fill="#162b26"/>
        <path d="M0,382 C300,366 520,390 820,376 C1100,362 1340,386 1600,372 L1600,400 L0,400 Z" fill="#162b26"/>
        {label(sx, sy - 112, 'LUMBINI')}
    </svg>
</div>
'''
open(OUT, 'w').write(blade)
print(len(blade))
