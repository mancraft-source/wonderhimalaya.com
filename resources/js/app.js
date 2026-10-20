document.documentElement.classList.add('js');

// Scroll-reveal for elements marked with [data-reveal]; respects reduced motion via CSS.
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

const observeReveals = () => document.querySelectorAll('[data-reveal]:not(.is-visible)').forEach((el) => revealObserver.observe(el));

document.addEventListener('DOMContentLoaded', observeReveals);
document.addEventListener('livewire:navigated', observeReveals);

// Header becomes solid after scrolling past the hero.
const header = document.querySelector('[data-site-header]');
if (header) {
    const onScroll = () => header.toggleAttribute('data-scrolled', window.scrollY > 40);
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
}

// Subtle parallax for hero media (≤ 8%).
const parallax = document.querySelectorAll('[data-parallax]');
if (parallax.length && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    window.addEventListener(
        'scroll',
        () => {
            const y = window.scrollY;
            parallax.forEach((el) => {
                el.style.transform = `translate3d(0, ${Math.min(y * 0.08, 120)}px, 0)`;
            });
        },
        { passive: true },
    );
}
