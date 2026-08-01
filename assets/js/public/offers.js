document.addEventListener('DOMContentLoaded', () => {
    initOffersLoadMore();
});

function initOffersLoadMore() {
    const btn = document.querySelector('[data-offers-load-more]');
    if (!btn) return;

    const originalLabel = btn.textContent;

    btn.addEventListener('click', () => {
        const grid = document.querySelector('[data-offers-grid]');
        const params = new URLSearchParams({
            mode: btn.dataset.mode,
            category: btn.dataset.category || '',
            sort: btn.dataset.sort,
            offset: btn.dataset.offset,
        });

        btn.disabled = true;
        btn.textContent = '...';

        fetch(`/offres/charger-plus?${params.toString()}`)
            .then((response) => response.json())
            .then((data) => {
                grid.insertAdjacentHTML('beforeend', data.html);

                if (data.hasMore) {
                    btn.dataset.offset = data.nextOffset;
                    btn.disabled = false;
                    btn.textContent = originalLabel;
                } else {
                    btn.remove(); // Plus rien à charger : le bouton disparaît complètement
                }
            });
    });
}