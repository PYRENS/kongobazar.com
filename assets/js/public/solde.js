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

// Animation "KongoBazar" : Kongo en noir, Bazar en bleu, chaque lettre décalée pour former une vague.
function soldeCreateLoader() {
    const el = document.createElement('div');
    el.className = 'solde-loader';
    el.setAttribute('role', 'status');
    el.setAttribute('aria-label', 'Chargement');
    el.innerHTML = 'KongoBazar'.split('').map((ch, i) =>
        `<span class="solde-loader-letter${i < 5 ? ' is-kongo' : ''}" style="animation-delay:${(i * 0.09).toFixed(2)}s">${ch}</span>`
    ).join('');
    return el;
}

// Laisse l'animation visible au moins `ms` millisecondes, même si le serveur répond très vite.
function soldeMinDelay(promise, ms) {
    const started = Date.now();
    return promise.then((value) => {
        const wait = Math.max(0, ms - (Date.now() - started));
        return new Promise((resolve) => setTimeout(() => resolve(value), wait));
    });
}

function soldeFetchHtml(url) {
    return fetch(url).then((r) => {
        if (!r.ok) throw new Error('HTTP ' + r.status);
        return r.text();
    });
}

function initSoldeInfiniteScroll() {
    const container = document.getElementById('soldeProductsContainer');
    if (!container) return;

    // Nombre de lots chargés automatiquement avant le bouton "Voir plus" (réglé dans l'admin de la campagne)
    const rawAuto = container.dataset.autoBatches;
    const autoLimit = rawAuto === undefined ? 3 : Math.max(0, parseInt(rawAuto, 10) || 0);

    const footer = document.querySelector('footer.site-footer');
    const shortcut = document.getElementById('soldeFooterShortcut');

    let autoLoaded = 0;       // lots chargés automatiquement depuis le début (ou depuis le dernier filtre)
    let busy = false;         // un chargement est en cours
    let paused = false;       // pause pendant le saut vers le footer
    let footerVisible = false; // le footer est à l'écran : le chargement automatique est bloqué
    let generation = 0;       // change à chaque rechargement (filtres) pour ignorer les réponses périmées

    function appendHtml(html) {
        const temp = document.createElement('div');
        temp.innerHTML = html;
        Array.from(temp.children).forEach((child) => container.appendChild(child));
    }

    // Charge le lot suivant (avec l'animation). Résout quand le lot est ajouté.
    function loadLot(sentinel) {
        const gen = generation;
        const loader = soldeCreateLoader();
        container.insertBefore(loader, sentinel);

        return soldeMinDelay(soldeFetchHtml(soldeBuildUrl(sentinel.dataset.nextOffset)), 400)
            .then((html) => {
                loader.remove();
                if (gen !== generation) return;
                sentinel.remove();
                appendHtml(html);
                // Saut vers le footer en cours pendant le chargement : on y retourne
                if (paused && footer) footer.scrollIntoView({ behavior: 'auto', block: 'start' });
            })
            .catch((err) => {
                loader.remove();
                throw err;
            });
    }

    // Après le nombre de lots automatiques : un bouton, un lot par clic, le footer reste accessible.
    function showLoadMoreButton(sentinel) {
        if (container.querySelector('.solde-load-more')) return;

        const wrap = document.createElement('div');
        wrap.className = 'solde-load-more';

        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'solde-load-more-btn';
        btn.innerHTML = '<i class="bi bi-arrow-down-circle"></i> Voir plus de produits';

        btn.addEventListener('click', () => {
            if (busy) return;
            busy = true;
            wrap.remove();
            loadLot(sentinel)
                .then(() => { busy = false; watchSentinel(); })
                .catch(() => { busy = false; showLoadMoreButton(sentinel); });
        });

        wrap.appendChild(btn);
        container.insertBefore(wrap, sentinel);
    }

    function watchSentinel() {
        const sentinel = container.querySelector('[data-solde-sentinel]');
        if (!sentinel) return;

        if (soldeObserver) soldeObserver.disconnect();
        soldeObserver = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting || busy || paused || footerVisible) return;
                soldeObserver.unobserve(sentinel);

                if (autoLoaded >= autoLimit) {
                    showLoadMoreButton(sentinel);
                    return;
                }

                busy = true;
                loadLot(sentinel)
                    .then(() => { autoLoaded++; busy = false; watchSentinel(); })
                    .catch(() => { busy = false; setTimeout(watchSentinel, 3000); });
            });
        }, { rootMargin: '400px' });

        soldeObserver.observe(sentinel);
    }

    // Surveille le footer : bloque le chargement automatique tant qu'il est visible,
    // masque le raccourci "Infos & aide", et relance le chargement quand le footer sort de l'écran.
    if (footer) {
        new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                footerVisible = entry.isIntersecting;
                if (shortcut) shortcut.classList.toggle('is-hidden', footerVisible);
                if (footerVisible) {
                    paused = false; // arrivé : c'est footerVisible qui bloque désormais
                } else {
                    watchSentinel();
                }
            });
        }).observe(footer);
    }

    // Raccourci "Infos & aide" : défilement direct jusqu'au footer, chargement automatique en pause.
    if (shortcut && footer) {
        shortcut.addEventListener('click', () => {
            paused = true;
            const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            footer.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'start' });
            // Filet de sécurité : si le footer n'a pas été atteint, on lève la pause
            setTimeout(() => {
                paused = false;
                if (!footerVisible) watchSentinel();
            }, 2500);
        });
    }

    watchSentinel();

    // Exposé pour que le tiroir de filtres puisse relancer un chargement propre.
    window.soldeRestartFeed = function () {
        generation++;
        const gen = generation;
        autoLoaded = 0;
        busy = false;

        container.classList.add('is-loading');
        const loader = soldeCreateLoader();
        loader.classList.add('solde-loader--overlay');
        container.appendChild(loader);

        soldeMinDelay(soldeFetchHtml(soldeBuildUrl(0)), 400)
            .then((html) => {
                if (gen !== generation) return;
                container.classList.remove('is-loading');
                container.innerHTML = html;
                watchSentinel();
            })
            .catch(() => {
                if (gen !== generation) return;
                container.classList.remove('is-loading');
                loader.remove();
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