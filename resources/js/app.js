document.documentElement.classList.add('js');

const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

/* ------------------------------------------------------------------
 | Scroll reveals: [data-reveal], [data-stagger] (children), [data-clip]
 * ------------------------------------------------------------------ */
const revealObserver = new IntersectionObserver(
    (entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                revealObserver.unobserve(entry.target);
            }
        });
    },
    { rootMargin: '0px 0px -8% 0px', threshold: 0.08 },
);

function observeReveals(root = document) {
    root.querySelectorAll('[data-stagger]').forEach((group) => {
        [...group.children].forEach((child, i) => child.style.setProperty('--d', String(i)));
    });
    root.querySelectorAll('[data-reveal]:not(.is-visible), [data-stagger]:not(.is-visible), [data-clip]:not(.is-visible), [data-draw]:not(.is-visible)').forEach((el) =>
        revealObserver.observe(el),
    );
}

/* ------------------------------------------------------------------
 | Count-up numbers: <span data-count="2,500+">2,500+</span>
 * ------------------------------------------------------------------ */
const countObserver = new IntersectionObserver(
    (entries) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting) return;
            countObserver.unobserve(entry.target);
            const el = entry.target;
            const raw = el.dataset.count;
            const match = raw.match(/^([^\d]*)([\d,.]+)(.*)$/);
            if (!match || reducedMotion) return;
            const [, prefix, number, suffix] = match;
            const target = parseFloat(number.replace(/,/g, ''));
            const decimals = (number.split('.')[1] || '').length;
            const start = performance.now();
            const duration = 1800;
            const tick = (now) => {
                const p = Math.min(1, (now - start) / duration);
                const eased = 1 - Math.pow(1 - p, 4);
                el.textContent = prefix + (target * eased).toLocaleString('en-US', { minimumFractionDigits: decimals, maximumFractionDigits: decimals }) + suffix;
                if (p < 1) requestAnimationFrame(tick);
            };
            requestAnimationFrame(tick);
        });
    },
    { threshold: 0.4 },
);

/* ------------------------------------------------------------------
 | Hero slideshow: [data-hero] with .hero-slide + .hero-progress items
 * ------------------------------------------------------------------ */
function initHero(hero) {
    const slides = [...hero.querySelectorAll('.hero-slide')];
    const bars = [...hero.querySelectorAll('.hero-progress')];
    const captions = [...hero.querySelectorAll('[data-hero-caption]')];
    const ms = parseInt(hero.dataset.interval || '7000', 10);
    hero.style.setProperty('--slide-ms', ms + 'ms');
    let index = 0;
    let timer = null;

    const show = (i) => {
        index = (i + slides.length) % slides.length;
        slides.forEach((s, k) => s.classList.toggle('is-active', k === index));
        captions.forEach((c, k) => c.toggleAttribute('hidden', k !== index));
        bars.forEach((b, k) => {
            b.classList.remove('is-active', 'is-done');
            void b.offsetWidth;
            if (k < index) b.classList.add('is-done');
            if (k === index) b.classList.add('is-active');
        });
    };
    const play = () => {
        clearInterval(timer);
        if (slides.length > 1 && !reducedMotion) timer = setInterval(() => show(index + 1), ms);
    };

    bars.forEach((bar, k) => bar.addEventListener('click', () => { show(k); play(); }));
    hero.addEventListener('mouseenter', () => clearInterval(timer));
    hero.addEventListener('mouseleave', play);
    document.addEventListener('visibilitychange', () => (document.hidden ? clearInterval(timer) : play()));

    let touchX = null;
    hero.addEventListener('touchstart', (e) => (touchX = e.touches[0].clientX), { passive: true });
    hero.addEventListener('touchend', (e) => {
        if (touchX === null) return;
        const dx = e.changedTouches[0].clientX - touchX;
        if (Math.abs(dx) > 50) { show(index + (dx < 0 ? 1 : -1)); play(); }
        touchX = null;
    });

    show(0);
    play();
}

/* ------------------------------------------------------------------
 | Carousel: [data-carousel] > [data-track] (scroll-snap), prev/next, progress
 * ------------------------------------------------------------------ */
function initCarousel(root) {
    const track = root.querySelector('[data-track]');
    const progress = root.querySelector('[data-progress]');
    const prev = root.querySelector('[data-prev]');
    const next = root.querySelector('[data-next]');
    if (!track) return;

    const step = () => (track.querySelector(':scope > *')?.getBoundingClientRect().width || 300) + 24;
    const update = () => {
        const max = track.scrollWidth - track.clientWidth;
        const p = max > 0 ? track.scrollLeft / max : 1;
        if (progress) progress.style.transform = `scaleX(${Math.max(0.08, p)})`;
        if (prev) prev.disabled = track.scrollLeft <= 4;
        if (next) next.disabled = track.scrollLeft >= max - 4;
    };

    prev?.addEventListener('click', () => track.scrollBy({ left: -step(), behavior: 'smooth' }));
    next?.addEventListener('click', () => track.scrollBy({ left: step(), behavior: 'smooth' }));
    track.addEventListener('scroll', update, { passive: true });
    window.addEventListener('resize', update);

    // Mouse drag-to-scroll (touch already scrolls natively).
    let down = false;
    let startX = 0;
    let startLeft = 0;
    let moved = false;
    track.addEventListener('pointerdown', (e) => {
        if (e.pointerType !== 'mouse') return;
        down = true; moved = false; startX = e.clientX; startLeft = track.scrollLeft;
        track.style.scrollSnapType = 'none';
    });
    window.addEventListener('pointermove', (e) => {
        if (!down) return;
        const dx = e.clientX - startX;
        if (Math.abs(dx) > 5) moved = true;
        track.scrollLeft = startLeft - dx;
    });
    window.addEventListener('pointerup', () => {
        if (!down) return;
        down = false;
        track.style.scrollSnapType = '';
    });
    track.addEventListener('click', (e) => { if (moved) { e.preventDefault(); e.stopPropagation(); } }, true);
    track.addEventListener('dragstart', (e) => e.preventDefault());

    update();
}

/* ------------------------------------------------------------------
 | Parallax: [data-parallax="0.08"] (fraction of scroll, relative to element)
 * ------------------------------------------------------------------ */
function initParallax() {
    const items = [...document.querySelectorAll('[data-parallax]')];
    if (!items.length || reducedMotion) return;
    let ticking = false;
    const render = () => {
        const vh = window.innerHeight;
        items.forEach((el) => {
            const speed = parseFloat(el.dataset.parallax || '0.08') || 0.08;
            const rect = el.parentElement.getBoundingClientRect();
            if (rect.bottom < -200 || rect.top > vh + 200) return;
            const offset = (rect.top + rect.height / 2 - vh / 2) * -speed;
            el.style.transform = `translate3d(0, ${offset.toFixed(1)}px, 0)`;
        });
        ticking = false;
    };
    window.addEventListener('scroll', () => { if (!ticking) { ticking = true; requestAnimationFrame(render); } }, { passive: true });
    render();
}

/* ------------------------------------------------------------------
 | Header: solid after scrolling past the top.
 * ------------------------------------------------------------------ */
function initHeader() {
    const header = document.querySelector('[data-site-header]');
    if (!header) return;
    const onScroll = () => header.toggleAttribute('data-scrolled', window.scrollY > 40);
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
}

/* ------------------------------------------------------------------
 | Altimeter + header ridge: scroll progress as a climb to 8,849 m.
 * ------------------------------------------------------------------ */
function initAltimeter() {
    const meter = document.querySelector('[data-altimeter]');
    const header = document.querySelector('[data-site-header]');
    const base = meter ? Number(meter.dataset.base) : 0;
    const summit = meter ? Number(meter.dataset.summit) : 0;
    const waypoints = meter ? JSON.parse(meter.dataset.waypoints || '[]') : [];
    const value = meter?.querySelector('[data-alt-value]');
    const place = meter?.querySelector('[data-alt-place]');
    let idle;
    let ticking = false;

    const render = () => {
        const max = document.documentElement.scrollHeight - window.innerHeight;
        const p = max > 0 ? Math.min(1, Math.max(0, window.scrollY / max)) : 0;
        header?.style.setProperty('--read', p.toFixed(4));
        if (meter) {
            const alt = Math.round(base + p * (summit - base));
            meter.style.setProperty('--p', p.toFixed(4));
            meter.toggleAttribute('data-active', window.scrollY > window.innerHeight * 0.6);
            value.textContent = `${alt.toLocaleString('en-US')} m`;
            place.textContent = [...waypoints].reverse().find(([, a]) => a <= alt)?.[0] ?? '';
        }
        ticking = false;
    };

    window.addEventListener('scroll', () => {
        if (meter) {
            meter.setAttribute('data-moving', '');
            clearTimeout(idle);
            idle = setTimeout(() => meter.removeAttribute('data-moving'), 1400);
        }
        if (!ticking) { ticking = true; requestAnimationFrame(render); }
    }, { passive: true });
    window.addEventListener('resize', render, { passive: true });
    render();
}

/* ------------------------------------------------------------------
 | "On this page" index: highlight the section being read.
 * ------------------------------------------------------------------ */
function initToc(nav) {
    const links = [...nav.querySelectorAll('[data-toc-link]')];
    const targets = links.map((a) => document.getElementById(a.hash.slice(1))).filter(Boolean);
    if (!targets.length) return;
    const update = () => {
        const line = window.innerHeight * 0.3;
        let current = targets[0];
        targets.forEach((t) => { if (t.getBoundingClientRect().top <= line) current = t; });
        links.forEach((a) => a.toggleAttribute('data-active', a.hash === `#${current.id}`));
    };
    window.addEventListener('scroll', update, { passive: true });
    update();
}

/**
 * Smooth open/close for native <details data-accordion> (FAQs). The element stays a real <details>, so it works
 * without JS and with "Expand all"; this only animates the height and fades the answer up.
 */
function initAccordion(details) {
    const summary = details.querySelector('summary');
    const body = details.querySelector('[data-accordion-body]');
    if (!summary || !body || reducedMotion) return;
    let animation = null;

    summary.addEventListener('click', (event) => {
        event.preventDefault();
        const closing = details.open && !details.dataset.closing;
        const from = details.getBoundingClientRect().height;
        animation?.cancel();
        details.style.overflow = 'hidden';

        if (closing) {
            details.dataset.closing = '1';
        } else {
            delete details.dataset.closing;
            details.open = true;
            body.animate([{ opacity: 0, transform: 'translateY(-6px)' }, { opacity: 1, transform: 'none' }], { duration: 450, delay: 80, easing: 'cubic-bezier(0.22, 1, 0.36, 1)', fill: 'backwards' });
        }

        const to = closing ? summary.offsetHeight : details.scrollHeight;
        animation = details.animate({ height: [`${from}px`, `${to}px`] }, { duration: closing ? 320 : 480, easing: 'cubic-bezier(0.22, 1, 0.36, 1)' });
        animation.onfinish = () => {
            if (details.dataset.closing) details.open = false;
            delete details.dataset.closing;
            details.style.overflow = '';
            animation = null;
        };
    });
}

function boot() {
    observeReveals();
    document.querySelectorAll('[data-count]').forEach((el) => countObserver.observe(el));
    document.querySelectorAll('[data-hero]').forEach(initHero);
    document.querySelectorAll('[data-carousel]').forEach(initCarousel);
    initParallax();
    initHeader();
    initAltimeter();
    document.querySelectorAll('[data-toc]').forEach(initToc);
    document.querySelectorAll('details[data-accordion]').forEach(initAccordion);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
} else {
    boot();
}
document.addEventListener('livewire:navigated', () => observeReveals());
