/* ==========================================================================
   Carrousel "Catégorie" (accueil, sous le Hero) — 2 rangées.
   Flèches : avance/recule d'un "écran" de colonnes ; glissement tactile natif
   (overflow-x + scroll-snap). Les flèches se masquent aux extrémités et
   disparaissent entièrement quand tout tient à l'écran.
   ========================================================================== */
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-cat-carousel]').forEach((carousel) => {
        const track = carousel.querySelector('[data-cat-carousel-track]');
        const prevBtn = carousel.querySelector('[data-cat-carousel-prev]');
        const nextBtn = carousel.querySelector('[data-cat-carousel-next]');
        if (!track) return;

        // Largeur d'un "écran" = largeur visible hors marges intérieures + un écart de colonne.
        function getStep() {
            const style = window.getComputedStyle(track);
            const padding = parseFloat(style.paddingLeft) + parseFloat(style.paddingRight);
            const gap = parseFloat(style.columnGap) || 0;
            return Math.max(100, track.clientWidth - padding + gap);
        }

        function updateArrows() {
            const maxScroll = track.scrollWidth - track.clientWidth;
            prevBtn.hidden = track.scrollLeft <= 2;
            nextBtn.hidden = track.scrollLeft >= maxScroll - 2;
        }

        prevBtn.addEventListener('click', () => track.scrollBy({ left: -getStep(), behavior: 'smooth' }));
        nextBtn.addEventListener('click', () => track.scrollBy({ left: getStep(), behavior: 'smooth' }));

        let ticking = false;
        track.addEventListener('scroll', () => {
            if (ticking) return;
            ticking = true;
            requestAnimationFrame(() => { updateArrows(); ticking = false; });
        }, { passive: true });

        window.addEventListener('resize', updateArrows);
        window.addEventListener('load', updateArrows); // les images peuvent changer les hauteurs
        updateArrows();
    });
});