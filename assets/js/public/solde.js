document.addEventListener('DOMContentLoaded', () => {
    initSoldeInfiniteScroll();
    initSoldeFilterDrawer();
});

let soldeCurrentFilters = { category: '', brand: '', min_price: '', max_price: '' };
let soldeObserver = null;

function soldeBuildUrl(offset) {
    const params = new URLSearchParams({ offset });
    if (soldeCurrentFilters.category) params.set('category', soldeCurrentFilters.category);
    if (soldeCurrentFilters.brand) params.set('brand', soldeCurrentFilters.brand);
    if (soldeCurrentFilters.min_price) params.set('min_price', soldeCurrentFilters.min_price);
    if (soldeCurrentFilters.max_price) params.set('max_price', soldeCurrentFilters.max_price);
    return '/accueil-solde/produits?' + params.toString();
}

function initSoldeInfiniteScroll() {
    const container = document.getElementById('soldeProductsContainer');
    if (!container) return;

    function watchSentinel() {
        const sentinel = container.querySelector('[data-solde-sentinel]');
        if (!sentinel) return;

        if (soldeObserver) soldeObserver.disconnect();
        soldeObserver = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) return;
                soldeObserver.unobserve(sentinel);
                const offset = sentinel.dataset.nextOffset;
                fetch(soldeBuildUrl(offset))
                    .then((r) => r.text())
                    .then((html) => {
                        sentinel.remove();
                        const temp = document.createElement('div');
                        temp.innerHTML = html;
                        Array.from(temp.children).forEach((child) => container.appendChild(child));
                        watchSentinel();
                    });
            });
        }, { rootMargin: '400px' });

        soldeObserver.observe(sentinel);
    }

    watchSentinel();

    // Exposé pour que le tiroir de filtres puisse relancer un chargement propre.
    window.soldeRestartFeed = function () {
        fetch(soldeBuildUrl(0))
            .then((r) => r.text())
            .then((html) => {
                container.innerHTML = html;
                watchSentinel();
            });
    };
}

function initSoldeFilterDrawer() {
    const trigger = document.getElementById('soldeFilterTrigger');
    const overlay = document.getElementById('soldeFilterOverlay');
    const closeBtn = document.getElementById('soldeFilterClose');
    const applyBtn = document.getElementById('soldeFilterApply');
    const resetBtn = document.getElementById('soldeFilterReset');
    if (!trigger || !overlay) return;

    trigger.addEventListener('click', () => overlay.classList.add('solde-filter-open'));
    closeBtn.addEventListener('click', () => overlay.classList.remove('solde-filter-open'));
    overlay.addEventListener('click', (e) => {
        if (e.target === overlay) overlay.classList.remove('solde-filter-open');
    });

    applyBtn.addEventListener('click', () => {
        const category = document.getElementById('soldeFilterCategory');
        const brand = document.getElementById('soldeFilterBrand');
        const minPrice = document.getElementById('soldeFilterMinPrice');
        const maxPrice = document.getElementById('soldeFilterMaxPrice');

        soldeCurrentFilters = {
            category: category ? category.value : '',
            brand: brand ? brand.value : '',
            min_price: minPrice ? minPrice.value : '',
            max_price: maxPrice ? maxPrice.value : '',
        };

        overlay.classList.remove('solde-filter-open');
        if (window.soldeRestartFeed) window.soldeRestartFeed();
    });

    resetBtn.addEventListener('click', () => {
        ['soldeFilterCategory', 'soldeFilterBrand', 'soldeFilterMinPrice', 'soldeFilterMaxPrice'].forEach((id) => {
            const el = document.getElementById(id);
            if (el) el.value = '';
        });
        soldeCurrentFilters = { category: '', brand: '', min_price: '', max_price: '' };
        overlay.classList.remove('solde-filter-open');
        if (window.soldeRestartFeed) window.soldeRestartFeed();
    });
}
