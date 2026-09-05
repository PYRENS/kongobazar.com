/* ==========================================================================
   "Top Catégorie" — défilement horizontal par flèches, 7 cartes à la fois,
   avec une animation qui ralentit progressivement vers la fin (ease-out),
   plus prononcée que le simple scroll-behavior:smooth du navigateur.
   ========================================================================== */
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-scroll-track]').forEach((track) => {
        const wrap = track.closest('.home-top-categories');
        if (!wrap) return;

        const prevBtn = wrap.querySelector('[data-carousel-prev]');
        const nextBtn = wrap.querySelector('[data-carousel-next]');
        const CARDS_PER_CLICK = 7;
        const DURATION = 750; // ms

        function getStepDistance() {
            const firstCard = track.querySelector('.top-category-item');
            if (!firstCard) return 400;
            const style = window.getComputedStyle(track);
            const gap = parseFloat(style.columnGap || style.gap || 8);
            return (firstCard.offsetWidth + gap) * CARDS_PER_CLICK;
        }

        // ease-out quart : démarre franchement vite, ralentit de façon très marquée en approchant de la fin.
        function easeOutCubic(t) {
            return 1 - Math.pow(1 - t, 4);
        }

        let animationFrame = null;
        function animateScrollBy(delta) {
            if (animationFrame) cancelAnimationFrame(animationFrame);

            const start = track.scrollLeft;
            const target = start + delta;
            const startTime = performance.now();

            function step(now) {
                const elapsed = now - startTime;
                const progress = Math.min(elapsed / DURATION, 1);
                track.scrollLeft = start + (target - start) * easeOutCubic(progress);

                if (progress < 1) {
                    animationFrame = requestAnimationFrame(step);
                } else {
                    animationFrame = null;
                }
            }

            animationFrame = requestAnimationFrame(step);
        }

        if (prevBtn) prevBtn.addEventListener('click', () => animateScrollBy(-getStepDistance()));
        if (nextBtn) nextBtn.addEventListener('click', () => animateScrollBy(getStepDistance()));
    });
});
