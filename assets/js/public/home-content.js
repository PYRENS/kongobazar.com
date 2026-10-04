/* ==========================================================================
   KongoBazar — JS du contenu de la page d'accueil
   Vanilla JS, sans dépendance.
   ========================================================================== */
document.addEventListener('DOMContentLoaded', () => {
    function syncMiniCarousels() {
        regroupMiniCarouselForMobileGrid('bestSellersMiniCarousel', 6, 3);
        regroupMiniCarouselForMobileGrid('newArrivalsMiniCarousel', 6, 3);
        regroupMiniCarouselForMobileGrid('recentlyViewedMiniCarousel', 10, 5);
    }
    syncMiniCarousels();
    initMiniCarousels();

    let miniCarouselResizeTimer;
    window.addEventListener('resize', () => {
        clearTimeout(miniCarouselResizeTimer);
        miniCarouselResizeTimer = setTimeout(syncMiniCarousels, 150);
    });
    initDealsCarousel();
    shortenDealCurrencyOnSmallScreens();
    initTrendingTabs();
    regroupTopVendorsForMobile();
    window.addEventListener('resize', regroupTopVendorsForMobile);
    initTrendingPagination();

    let trendingResizeTimeout;
    window.addEventListener('resize', () => {
        clearTimeout(trendingResizeTimeout);
        trendingResizeTimeout = setTimeout(initTrendingPagination, 200);
    });
    initNewItemsTabs();
    initComingSoonTabs();
    initIndividualSectionTabs();
    initCategoryBlockSortTabs();
    initGalleryThumbSwap();
    initCountdowns();
    initHotDealCard();
    initLatestBlogsCarousel();
    fillSidebarWithBanners();
    fillSidebarWithBanners('searchSidebarFiller', '.search-sidebar-col', '.search-results-col');
    fillSidebarWithBanners('categorySidebarFiller', '.search-sidebar-col', '.search-results-col');
    // initAddToCartButtons() retiré : géré désormais globalement (toutes pages) par cart-added-modal.js
});
/* --------------------------------------------------------------------------
   Carrousel "Ventes flash" — glissement carte par carte (pas page par page),
   pour ne jamais laisser de case vide même avec un nombre impair d'articles.
   Mécanisme dédié, séparé de initMiniCarousels() qui reste page-par-page
   pour les autres carrousels de la page.
   -------------------------------------------------------------------------- */
function shortenDealCurrencyOnSmallScreens() {
    if (window.innerWidth > 496) return;
    document.querySelectorAll('.home-deal-price .price-now, .home-deal-price .price-old').forEach((el) => {
        if (el.dataset.currencyShortened === '1') return;
        el.textContent = el.textContent.replace(/\s*USD\b/, ' $');
        el.dataset.currencyShortened = '1';
    });
}

function initDealsCarousel() {
    const AUTOPLAY_DELAY = 4000;

    document.querySelectorAll('[data-deals-carousel]').forEach((carousel) => {
        const viewport = carousel.querySelector('.home-deals-viewport');
        const track = carousel.querySelector('.home-deals-track');
        const prevBtn = carousel.querySelector('[data-carousel-prev]');
        const nextBtn = carousel.querySelector('[data-carousel-next]');
        if (!viewport || !track) return;

        const cards = Array.from(track.children);
        if (cards.length === 0) return;

        let itemsPerView = 2;
        let cardWidth = 0;
        let currentIndex = 0;
        let timer = null;

        // Lit l'espacement réellement appliqué par le CSS (.home-deals-track { gap }), au lieu
        // de le deviner avec un seuil séparé qui peut se désynchroniser du CSS.
        function currentGap() {
            return parseFloat(getComputedStyle(track).columnGap) || 0;
        }

        function layout() {
            const gap = currentGap();
            itemsPerView = Math.min(cards.length, 2);
            cardWidth = (viewport.offsetWidth - gap * (itemsPerView - 1)) / itemsPerView;
            cards.forEach((card) => {
                card.style.width = cardWidth + 'px';
            });
            currentIndex = Math.min(currentIndex, Math.max(0, cards.length - itemsPerView));
            applyPosition(false);
        }

        function applyPosition(animate) {
            const gap = currentGap();
            track.style.transition = animate ? 'margin-left 0.4s ease' : 'none';
            track.style.marginLeft = `-${currentIndex * (cardWidth + gap)}px`;
        }

        function totalPositions() {
            return Math.max(1, cards.length - itemsPerView + 1);
        }

        function goTo(index) {
            const total = totalPositions();
            currentIndex = ((index % total) + total) % total;
            applyPosition(true);
        }

        function next() { goTo(currentIndex + 1); }
        function prev() { goTo(currentIndex - 1); }

        function startAutoplay() {
            clearInterval(timer);
            if (totalPositions() > 1) timer = setInterval(next, AUTOPLAY_DELAY);
        }

        if (prevBtn) prevBtn.addEventListener('click', () => { prev(); startAutoplay(); });
        if (nextBtn) nextBtn.addEventListener('click', () => { next(); startAutoplay(); });

        carousel.addEventListener('mouseenter', () => clearInterval(timer));
        carousel.addEventListener('mouseleave', startAutoplay);

        // --- Glissement tactile (mobile) ---
        let touchStartX = 0;
        let touchDeltaX = 0;
        let isDragging = false;
        const SWIPE_THRESHOLD = 40;

        viewport.addEventListener('touchstart', (e) => {
            isDragging = true;
            touchStartX = e.touches[0].clientX;
            touchDeltaX = 0;
            clearInterval(timer);
            track.style.transition = 'none';
        }, { passive: true });

        viewport.addEventListener('touchmove', (e) => {
            if (!isDragging) return;
            touchDeltaX = e.touches[0].clientX - touchStartX;
            const basePosition = -currentIndex * (cardWidth + currentGap());
            track.style.marginLeft = `${basePosition + touchDeltaX}px`;
        }, { passive: true });

        viewport.addEventListener('touchend', () => {
            if (!isDragging) return;
            isDragging = false;
            if (touchDeltaX <= -SWIPE_THRESHOLD) {
                next();
            } else if (touchDeltaX >= SWIPE_THRESHOLD) {
                prev();
            } else {
                applyPosition(true);
            }
            startAutoplay();
        });

        let resizeTimeout;
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(layout, 150);
        });

        layout();
        startAutoplay();
    });
}
/* --------------------------------------------------------------------------
   Mini-carousels (colonne gauche : Meilleures ventes / Nouveaux articles,
   et Top Catégories) — pagination par point ou par flèche, glissement
   via margin-left (pas transform, qui posait un souci de rendu sur cette
   machine).
   -------------------------------------------------------------------------- */
/* --------------------------------------------------------------------------
   "Meilleures ventes" en mobile (≤575px) : au lieu de 3 produits empilés par
   page (comme sur desktop), on regroupe par 6 en grille 2 colonnes, glissable
   au doigt (scroll natif + snap), séparé du système page-par-page à transform
   utilisé par les autres mini-carrousels.
   -------------------------------------------------------------------------- */
function regroupMiniCarouselForMobileGrid(carouselId, mobileGroupSize = 6, desktopGroupSize = null) {
    const carousel = document.getElementById(carouselId);
    if (!carousel) return;

    const track = carousel.querySelector('.home-mini-carousel-track');
    const dotsWrap = carousel.querySelector('.home-mini-carousel-dots');
    if (!track) return;

    // Les produits d'origine ne sont mis en cache qu'une seule fois (ils sont peut-être déjà
    // répartis en plusieurs pages, rendues côté serveur) — chaque recalcul repart de cet
    // ensemble complet, jamais d'un état déjà transformé par un appel précédent.
    if (!carousel._allMiniProducts) {
        carousel._allMiniProducts = Array.from(track.querySelectorAll('.home-mini-product'));
    }
    const products = carousel._allMiniProducts;
    if (products.length === 0) return;

    const isMobile = window.innerWidth <= 991;
    const groupSize = isMobile ? mobileGroupSize : (desktopGroupSize || mobileGroupSize);

    // Rien à refaire si la taille de groupe n'a pas changé depuis le dernier calcul (un
    // redimensionnement qui reste du même côté du seuil 991px, par exemple).
    if (carousel.dataset.lastGroupSize === String(groupSize)) return;
    carousel.dataset.lastGroupSize = String(groupSize);

    const groups = [];
    for (let i = 0; i < products.length; i += groupSize) {
        groups.push(products.slice(i, i + groupSize));
    }

    track.innerHTML = '';
    groups.forEach((group) => {
        const page = document.createElement('div');
        page.className = 'home-mini-carousel-page';
        group.forEach((product) => page.appendChild(product));
        track.appendChild(page);
    });

    if (dotsWrap) {
        dotsWrap.innerHTML = '';
        groups.forEach((_, i) => {
            const dot = document.createElement('button');
            dot.type = 'button';
            dot.className = 'dot' + (i === 0 ? ' active' : '');
            dot.dataset.page = String(i);
            dotsWrap.appendChild(dot);
        });
    }

    if (isMobile) {
        carousel.dataset.mobileGridDone = '1';
        initMiniCarouselMobileScroll(carouselId);
    } else {
        delete carousel.dataset.mobileGridDone;
        wireDesktopMiniCarousel(carousel);
    }
}

function initMiniCarouselMobileScroll(carouselId) {
    const carousel = document.getElementById(carouselId);
    if (!carousel || carousel.dataset.mobileGridDone !== '1') return;

    const track = carousel.querySelector('.home-mini-carousel-track');
    const dots = carousel.querySelectorAll('.dot');
    const pages = track ? track.querySelectorAll('.home-mini-carousel-page') : [];
    if (!track || pages.length === 0) return;

    let currentIndex = 0;

    function setActiveDot(index) {
        const allDots = carousel.querySelectorAll('.dot');
        if (allDots[currentIndex]) allDots[currentIndex].classList.remove('active');
        currentIndex = index;
        if (allDots[currentIndex]) allDots[currentIndex].classList.add('active');
    }

    // Les points sont reconstruits à chaque regroupement : on leur attache de nouveaux
    // écouteurs à chaque fois (les anciens nœuds, eux, ont disparu avec dotsWrap.innerHTML).
    dots.forEach((dot, i) => {
        dot.addEventListener('click', () => {
            track.scrollTo({ left: pages[i].offsetLeft, behavior: 'smooth' });
        });
    });

    // Le train (track), lui, reste le même élément d'un regroupement à l'autre — sans ce
    // verrou, chaque redimensionnement empilerait un nouvel écouteur de scroll en double.
    if (track.dataset.scrollSyncWired === '1') return;
    track.dataset.scrollSyncWired = '1';

    let syncTimer = null;
    track.addEventListener('scroll', () => {
        clearTimeout(syncTimer);
        syncTimer = setTimeout(() => {
            const currentPages = track.querySelectorAll('.home-mini-carousel-page');
            let closest = 0;
            let closestDist = Infinity;
            currentPages.forEach((p, i) => {
                const d = Math.abs(p.offsetLeft - track.scrollLeft);
                if (d < closestDist) { closestDist = d; closest = i; }
            });
            if (closest !== currentIndex) setActiveDot(closest);
        }, 120);
    }, { passive: true });
}

function wireDesktopMiniCarousel(carousel) {
    const AUTOPLAY_DELAY = 4000;
    const track = carousel.querySelector('.home-mini-carousel-track, .home-deals-track, .home-top-categories-track');
    const dots = carousel.querySelectorAll('.dot');
    const prevBtn = carousel.querySelector('[data-carousel-prev]');
    const nextBtn = carousel.querySelector('[data-carousel-next]');
    if (!track) return;

    const pages = track.querySelectorAll(':scope > *');
    let currentPage = 0;
    let timer = null;
    const totalPages = pages.length || dots.length || 1;

    // Chaque page occupe 100% de la largeur du track (pas de pixels figés, pas de risque
    // de valeur qui dérape) ; on fait glisser avec un pourcentage, toujours relatif à la
    // largeur réelle du moment.
    pages.forEach((page) => {
        page.style.flex = '0 0 100%';
        page.style.width = '100%';
    });
    track.style.width = '100%';
    track.style.display = 'flex';
    track.style.transform = 'translateX(0%)';
    track.style.transition = 'transform 0.4s ease';
    carousel.style.overflow = 'hidden';

    function goToPage(index) {
        currentPage = index;
        track.style.transform = `translateX(-${currentPage * 100}%)`;
        dots.forEach((dot, i) => dot.classList.toggle('active', i === currentPage));
    }

    function next() {
        goToPage((currentPage + 1) % totalPages);
    }

    function startAutoplay() {
        clearInterval(timer);
        timer = setInterval(next, AUTOPLAY_DELAY);
    }

    dots.forEach((dot) => {
        dot.addEventListener('click', () => {
            goToPage(parseInt(dot.dataset.page, 10));
            startAutoplay();
        });
    });

    if (prevBtn) {
        prevBtn.addEventListener('click', () => {
            goToPage(Math.max(0, currentPage - 1));
            startAutoplay();
        });
    }
    if (nextBtn) {
        nextBtn.addEventListener('click', () => {
            goToPage(Math.min(totalPages - 1, currentPage + 1));
            startAutoplay();
        });
    }

    carousel.addEventListener('mouseenter', () => clearInterval(timer));
    carousel.addEventListener('mouseleave', startAutoplay);

    goToPage(0);
    if (totalPages > 1) startAutoplay();
}

function initMiniCarousels() {
    document.querySelectorAll('[data-mini-carousel]').forEach((carousel) => {
        if (carousel.dataset.mobileGridDone === '1') return;
        wireDesktopMiniCarousel(carousel);
    });
}
/* --------------------------------------------------------------------------
   Onglets "Articles tendances" (change de catégorie affichée)
   -------------------------------------------------------------------------- */
function initTrendingTabs() {
    document.querySelectorAll('.trending-tab').forEach((tab) => {
        tab.addEventListener('click', () => {
            const container = tab.closest('.home-trending');
            if (!container) return;
            container.querySelectorAll('.trending-tab').forEach((t) => {
                t.classList.remove('active');
                t.setAttribute('aria-selected', 'false');
            });
            container.querySelectorAll('.trending-panel').forEach((p) => p.classList.remove('active'));
            tab.classList.add('active');
            tab.setAttribute('aria-selected', 'true');
            const target = document.getElementById(tab.dataset.tabTarget);
            if (target) target.classList.add('active');
        });
    });
}

/* --------------------------------------------------------------------------
   Pagination interne de chaque panneau "Articles tendances" — flèches +
   pastilles pour naviguer entre les pages de produits d'un même onglet.
   -------------------------------------------------------------------------- */
/* --------------------------------------------------------------------------
   Pagination "Articles tendances" — une seule fonction, cohérente à toutes
   les résolutions. La liste complète des cartes est mise en cache au premier
   passage (panel._trendingAllCards), puis les pages sont reconstruites à
   chaque appel selon la taille de groupe qui correspond à la largeur actuelle
   — y compris en repassant d'une résolution à une autre dans la même session.
   -------------------------------------------------------------------------- */
function getTrendingGroupSize(panel) {
    if (window.innerWidth <= 414) return 4;
    if (window.innerWidth <= 796) return 9;

    // Option réservée aux panneaux qui le demandent explicitement : un nombre différent
    // spécifiquement entre 797px et cette largeur-ci (ex: "Consulté récemment", qui passe à
    // 10 par page ≤991px au lieu de son réglage desktop habituel).
    if (panel.dataset.perPage991 && window.innerWidth <= 991) {
        return parseInt(panel.dataset.perPage991, 10);
    }

    // Option réservée aux panneaux qui le demandent explicitement (data-paging-max-width) :
    // au-delà de cette largeur, plus de découpage du tout — grille plate complète, comme
    // avant. Les autres sections du site, qui ne posent pas cet attribut, ne sont pas
    // concernées et gardent leur comportement d'origine.
    const pagingMaxWidth = panel.dataset.pagingMaxWidth ? parseInt(panel.dataset.pagingMaxWidth, 10) : null;
    if (pagingMaxWidth && window.innerWidth > pagingMaxWidth) {
        return panel._trendingAllCards ? panel._trendingAllCards.length : 9999;
    }

    return parseInt(panel.dataset.desktopPerPage || '8', 10);
}

function initTrendingPanel(panel) {
    if (!panel._trendingAllCards) {
        panel._trendingAllCards = Array.from(panel.querySelectorAll('.product-card, .home-mini-product'));
        // Les cartes sont récupérées, mais les coquilles de pages générées par le
        // serveur (avant la création de la piste glissante par ce script) restent
        // vides dans le DOM une fois leurs cartes déplacées — on les retire ici.
        panel.querySelectorAll('.trending-product-page').forEach((p) => p.remove());
    }
    const allCards = panel._trendingAllCards;
    if (allCards.length === 0) return;

    const groupSize = getTrendingGroupSize(panel);
    if (panel.dataset.currentGroupSize === String(groupSize)) return;
    panel.dataset.currentGroupSize = String(groupSize);

    const groups = [];
    for (let i = 0; i < allCards.length; i += groupSize) {
        groups.push(allCards.slice(i, i + groupSize));
    }

    // Piste glissante : on la crée une fois, on la réutilise ensuite.
    let track = panel.querySelector('.trending-pages-track');
    if (!track) {
        track = document.createElement('div');
        track.className = 'trending-pages-track';
        const toolbar = panel.querySelector('.trending-panel-toolbar');
        if (toolbar && toolbar.nextSibling) {
            panel.insertBefore(track, toolbar.nextSibling);
        } else {
            panel.appendChild(track);
        }
    }
    track.innerHTML = '';
    groups.forEach((group) => {
        const pageDiv = document.createElement('div');
        pageDiv.className = 'trending-product-page';
        const grid = document.createElement('div');
        grid.className = panel.classList.contains('home-mini-carousel-as-panel') ? 'home-mini-product-grid' : 'home-product-grid';
        group.forEach((card) => grid.appendChild(card));
        pageDiv.appendChild(grid);
        track.appendChild(pageDiv);
    });

    const dotsWrap = panel.querySelector('.trending-page-dots');
    if (dotsWrap) {
        dotsWrap.innerHTML = '';
        groups.forEach((_, i) => {
            const dot = document.createElement('button');
            dot.type = 'button';
            dot.className = 'trending-page-dot' + (i === 0 ? ' active' : '');
            dot.dataset.page = String(i);
            dotsWrap.appendChild(dot);
        });
    }

    let prevBtn = panel.querySelector('.trending-page-arrow--prev');
    let nextBtn = panel.querySelector('.trending-page-arrow--next');
    const actions = panel.querySelector('.trending-panel-actions');
    const seeAllLink = panel.querySelector('.see-all-link');
    if (actions && !prevBtn) {
        prevBtn = document.createElement('button');
        prevBtn.type = 'button';
        prevBtn.className = 'trending-page-arrow trending-page-arrow--prev';
        prevBtn.innerHTML = '<i class="bi bi-chevron-left"></i>';
        actions.insertBefore(prevBtn, seeAllLink || actions.firstChild);
    }
    if (actions && !nextBtn) {
        nextBtn = document.createElement('button');
        nextBtn.type = 'button';
        nextBtn.className = 'trending-page-arrow trending-page-arrow--next';
        nextBtn.innerHTML = '<i class="bi bi-chevron-right"></i>';
        actions.insertBefore(nextBtn, seeAllLink || null);
    }

    const hasMultiple = groups.length > 1;
    if (prevBtn) prevBtn.style.display = hasMultiple ? '' : 'none';
    if (nextBtn) nextBtn.style.display = hasMultiple ? '' : 'none';

    let current = 0;
    const dots = panel.querySelectorAll('.trending-page-dot');
    const pageEls = Array.from(track.querySelectorAll('.trending-product-page'));
    let programmaticScroll = false;

    function setActiveState(index) {
        current = index;
        dots.forEach((d, i) => d.classList.toggle('active', i === current));
        if (prevBtn) prevBtn.disabled = current === 0;
        if (nextBtn) nextBtn.disabled = current === groups.length - 1;
    }

    function goTo(index) {
        programmaticScroll = true;
        track.scrollTo({ left: pageEls[index].offsetLeft, behavior: 'smooth' });
        setActiveState(index);
        window.setTimeout(() => { programmaticScroll = false; }, 500);
    }

    if (prevBtn) prevBtn.onclick = () => { if (current > 0) goTo(current - 1); };
    if (nextBtn) nextBtn.onclick = () => { if (current < groups.length - 1) goTo(current + 1); };
    dots.forEach((dot, i) => { dot.onclick = () => goTo(i); });

    let scrollSyncTimer = null;
    track.onscroll = () => {
        if (programmaticScroll) return;
        clearTimeout(scrollSyncTimer);
        scrollSyncTimer = setTimeout(() => {
            let closest = 0;
            let closestDist = Infinity;
            pageEls.forEach((p, i) => {
                const d = Math.abs(p.offsetLeft - track.scrollLeft);
                if (d < closestDist) { closestDist = d; closest = i; }
            });
            if (closest !== current) setActiveState(closest);
        }, 120);
    };

    setActiveState(0);
}

/* --------------------------------------------------------------------------
   "Top vendeur" — regroupe les cartes par 4 (2×2) et active le glissement,
   uniquement ≤414px. Au-dessus, la grille reste statique telle quelle.
   -------------------------------------------------------------------------- */
function regroupTopVendorsForMobile() {
    const grid = document.querySelector('.home-vendors-grid');
    if (!grid) return;

    const shouldBeMobile = window.innerWidth <= 414;
    const isMobile = grid.dataset.mobileRegrouped === '1';

    if (shouldBeMobile === isMobile) return;

    if (shouldBeMobile) {
        const cards = Array.from(grid.querySelectorAll('.vendor-card'));
        if (cards.length === 0) return;
        grid.dataset.originalHtml = grid.innerHTML;

        const GROUP_SIZE = 4;
        const groups = [];
        for (let i = 0; i < cards.length; i += GROUP_SIZE) {
            groups.push(cards.slice(i, i + GROUP_SIZE));
        }

        grid.innerHTML = '';
        groups.forEach((group) => {
            const page = document.createElement('div');
            page.className = 'vendors-page';
            group.forEach((card) => page.appendChild(card));
            grid.appendChild(page);
        });

        grid.dataset.mobileRegrouped = '1';
    } else if (grid.dataset.originalHtml) {
        grid.innerHTML = grid.dataset.originalHtml;
        grid.dataset.mobileRegrouped = '0';
    }
}

function initTrendingPagination() {
    document.querySelectorAll('.trending-panel').forEach((panel) => initTrendingPanel(panel));
}
/* --------------------------------------------------------------------------
   Onglets "Nouveauté" — même principe que "Articles tendances" (tout préchargé,
   simple bascule d'affichage, pas d'AJAX).
   -------------------------------------------------------------------------- */
function initNewItemsTabs() {
    document.querySelectorAll('.home-new-items .trending-tab').forEach((tab) => {
        tab.addEventListener('click', () => {
            const container = tab.closest('.home-new-items');
            if (!container) return;
            container.querySelectorAll('.new-items-tabs .trending-tab').forEach((t) => t.classList.remove('active'));
            container.querySelectorAll('.new-items-panel').forEach((p) => p.classList.remove('active'));
            tab.classList.add('active');
            const target = document.getElementById(tab.dataset.tabTarget);
            if (target) target.classList.add('active');
        });
    });
}
/* --------------------------------------------------------------------------
   Onglets "Prochainement" — même principe, "Voir tous" inclus dans le même groupe.
   -------------------------------------------------------------------------- */
function initComingSoonTabs() {
    document.querySelectorAll('.home-coming-soon .trending-tab').forEach((tab) => {
        tab.addEventListener('click', () => {
            const container = tab.closest('.home-coming-soon');
            if (!container) return;
            container.querySelectorAll('.coming-soon-tabs .trending-tab').forEach((t) => t.classList.remove('active'));
            container.querySelectorAll('.coming-soon-panel').forEach((p) => p.classList.remove('active'));
            tab.classList.add('active');
            const target = document.getElementById(tab.dataset.tabTarget);
            if (target) target.classList.add('active');
        });
    });
}
function initIndividualSectionTabs() {
    document.querySelectorAll('.home-individual-section .trending-tab').forEach((tab) => {
        tab.addEventListener('click', () => {
            const container = tab.closest('.home-individual-section');
            if (!container) return;
            container.querySelectorAll('.individual-tabs .trending-tab').forEach((t) => t.classList.remove('active'));
            container.querySelectorAll('.individual-panel').forEach((p) => p.classList.remove('active'));
            tab.classList.add('active');
            const target = document.getElementById(tab.dataset.tabTarget);
            if (target) target.classList.add('active');
        });
    });
}
/* --------------------------------------------------------------------------
   Onglets BEST SELLERS / NEW ARRIVALS / FEATURED dans chaque bloc catégorie
   -------------------------------------------------------------------------- */
function initCategoryBlockSortTabs() {
    document.querySelectorAll('.sort-tab').forEach((tab) => {
        tab.addEventListener('click', () => {
            const container = tab.closest('.category-block-main');
            if (!container) return;
            container.querySelectorAll('.sort-tab').forEach((t) => t.classList.remove('active'));
            container.querySelectorAll('.sort-panel').forEach((p) => p.classList.remove('active'));
            tab.classList.add('active');
            const target = document.getElementById(tab.dataset.sortTarget);
            if (target) target.classList.add('active');
        });
    });
}
/* --------------------------------------------------------------------------
   Galerie de vignettes au survol : remplace temporairement l'image principale
   -------------------------------------------------------------------------- */
function initGalleryThumbSwap() {
    document.querySelectorAll('.product-card').forEach((card) => {
        const mainImage = card.querySelector('[data-main-image]');
        const thumbs = card.querySelectorAll('.gallery-thumb');
        if (!mainImage || thumbs.length === 0) return;
        const originalSrc = mainImage.getAttribute('src');
        thumbs.forEach((thumb) => {
            thumb.addEventListener('mouseenter', () => {
                thumbs.forEach((t) => t.classList.remove('active'));
                thumb.classList.add('active');
                mainImage.setAttribute('src', thumb.dataset.swapImage);
            });
        });
        card.addEventListener('mouseleave', () => {
            mainImage.setAttribute('src', originalSrc);
            thumbs.forEach((t, i) => t.classList.toggle('active', i === 0));
        });
    });
}
/* --------------------------------------------------------------------------
   Comptes à rebours "Deals of the week" — le serveur fait foi (data-countdown-end
   est un horodatage ISO fourni par le contrôleur, jamais recalculé côté client).
   -------------------------------------------------------------------------- */
function initHotDealCard() {
    const card = document.querySelector('.hot-deal-card');
    if (!card) return;

    const track = card.querySelector('.hot-deal-slides');
    const prevBtn = card.querySelector('.hot-deal-arrow--prev');
    const nextBtn = card.querySelector('.hot-deal-arrow--next');
    if (!track.dataset.originalHtml) {
        track.dataset.originalHtml = track.innerHTML;
    }

    let current = 0;
    let restoreSnapTimer = null;

    function regroup() {
        const shouldPage = window.innerWidth <= 991;
        const isPaged = track.dataset.paged === '1';
        if (shouldPage === isPaged) return;

        if (shouldPage) {
            const originalSlides = Array.from(track.children);
            if (originalSlides.length <= 1) return;

            const GROUP_SIZE = 2;
            const pages = [];
            for (let i = 0; i < originalSlides.length; i += GROUP_SIZE) {
                pages.push(originalSlides.slice(i, i + GROUP_SIZE));
            }

            track.innerHTML = '';
            pages.forEach((group) => {
                const page = document.createElement('div');
                page.className = 'hot-deal-slide hot-deal-page';
                group.forEach((slide) => {
                    slide.classList.add('hot-deal-slide-inner');
                    slide.classList.remove('hot-deal-slide');
                    page.appendChild(slide);
                });
                track.appendChild(page);
            });
            track.dataset.paged = '1';
        } else {
            track.innerHTML = track.dataset.originalHtml;
            track.dataset.paged = '0';
        }
        current = 0;
        track.style.transform = '';
        track.scrollLeft = 0;
    }

    function goTo(index) {
        const slides = Array.from(track.children);
        if (slides.length <= 1) return;
        current = (index + slides.length) % slides.length;

        track.style.scrollSnapType = 'none';
        track.scrollTo({ left: slides[current].offsetLeft, behavior: 'smooth' });

        // Force le navigateur à repeindre l'écran tout de suite : sur certaines machines/GPU,
        // scrollLeft change en interne mais l'affichage ne se met pas à jour tant que rien d'autre
        // ne force un nouveau rendu — ce que les DevTools ouverts font sans qu'on s'en rende compte,
        // d'où le fonctionnement "normal" pendant l'inspection et le blocage une fois fermés.
        void track.offsetHeight;

        clearTimeout(restoreSnapTimer);
        restoreSnapTimer = setTimeout(() => { track.style.scrollSnapType = ''; }, 600);
    }

    regroup();
    window.addEventListener('resize', regroup);

    if (prevBtn) prevBtn.addEventListener('click', (e) => { e.preventDefault(); goTo(current - 1); });
    if (nextBtn) nextBtn.addEventListener('click', (e) => { e.preventDefault(); goTo(current + 1); });
}

/**
 * Choisit la meilleure combinaison de bannières "bouche-trou" (parmi celles
 * disponibles, déjà présentes dans le DOM mais masquées) pour approcher au
 * plus près l'écart de hauteur avec la colonne centrale, SANS le dépasser —
 * plutôt que de les révéler au hasard jusqu'à déborder sur la dernière.
 * Algorithme "meilleur ajustement décroissant" : mesure d'abord la hauteur
 * réelle de chaque bannière (largeur fixe 270px, donc hauteur = ratio de
 * l'image), les trie de la plus grande à la plus petite, puis ajoute chaque
 * bannière si — et seulement si — elle tient encore dans l'espace restant.
 */
function fillSidebarWithBanners(containerId = 'homeSidebarFiller', sidebarSelector = '.home-left-col', mainSelector = '.home-center-col') {
    const container = document.getElementById(containerId);
    if (!container) return;

    const leftCol = document.querySelector(sidebarSelector);
    const centerCol = document.querySelector(mainSelector);
    if (!leftCol || !centerCol) return;

    const GAP = 12; // doit correspondre à .home-sidebar-filler { gap: 12px; }
    const items = Array.from(container.querySelectorAll('.home-sidebar-filler-item'));
    if (items.length === 0) return;

    function measureHeight(item) {
        return new Promise((resolve) => {
            const img = item.querySelector('img');
            const compute = () => {
                const ratio = img.naturalHeight / img.naturalWidth;
                resolve({ item, height: (isFinite(ratio) && ratio > 0) ? 270 * ratio : 0 });
            };
            if (img.complete && img.naturalWidth > 0) {
                compute();
            } else {
                img.addEventListener('load', compute, { once: true });
                img.addEventListener('error', () => resolve({ item, height: 0 }), { once: true });
                // Filet de sécurité : si ni "load" ni "error" ne se déclenchent (cas
                // imprévu), on n'attend jamais indéfiniment plus de 4 secondes.
                window.setTimeout(() => resolve({ item, height: 0 }), 4000);
            }
        });
    }

    // On laisse le layout se stabiliser avant de mesurer l'écart cible.
    window.setTimeout(() => {
        Promise.all(items.map(measureHeight)).then((measured) => {
            const targetGap = centerCol.getBoundingClientRect().height - leftCol.getBoundingClientRect().height;
            if (targetGap <= 0) return;

            measured.sort((a, b) => b.height - a.height);

            let used = 0;
            measured.forEach(({ item, height }) => {
                const cost = used === 0 ? height : height + GAP;
                if (used + cost <= targetGap) {
                    item.hidden = false;
                    used += cost;
                }
            });
        });
    }, 300);
}

function initLatestBlogsCarousel() {
    const track = document.querySelector('[data-blog-track]');
    if (!track) return;
    const prevBtn = document.querySelector('[data-blog-prev]');
    const nextBtn = document.querySelector('[data-blog-next]');
    const cards = Array.from(track.children);
    if (cards.length === 0) return;

    const scrollByOne = (dir) => {
        const cardWidth = cards[0].getBoundingClientRect().width + 20;
        track.scrollBy({ left: dir * cardWidth, behavior: 'smooth' });
    };

    if (prevBtn) prevBtn.addEventListener('click', () => scrollByOne(-1));
    if (nextBtn) nextBtn.addEventListener('click', () => scrollByOne(1));
}

function initCountdowns() {
    const countdowns = document.querySelectorAll('[data-countdown-end]');
    if (countdowns.length === 0) return;
    function tick() {
        countdowns.forEach((el) => {
            const endValue = el.dataset.countdownEnd;
            if (!endValue) return;
            const end = new Date(endValue).getTime();
            const now = Date.now();
            const diff = end - now;
            const daysEl = el.querySelector('[data-cd-days]');
            const hoursEl = el.querySelector('[data-cd-hours]');
            const minsEl = el.querySelector('[data-cd-mins]');
            const secsEl = el.querySelector('[data-cd-secs]');
            if (diff <= 0) {
                if (daysEl) daysEl.textContent = '00';
                if (hoursEl) hoursEl.textContent = '00';
                if (minsEl) minsEl.textContent = '00';
                if (secsEl) secsEl.textContent = '00';
                return;
            }
            const days = Math.floor(diff / (1000 * 60 * 60 * 24));
            const hours = Math.floor((diff / (1000 * 60 * 60)) % 24);
            const mins = Math.floor((diff / (1000 * 60)) % 60);
            const secs = Math.floor((diff / 1000) % 60);
            if (daysEl) daysEl.textContent = String(days).padStart(2, '0');
            if (hoursEl) hoursEl.textContent = String(hours).padStart(2, '0');
            if (minsEl) minsEl.textContent = String(mins).padStart(2, '0');
            if (secsEl) secsEl.textContent = String(secs).padStart(2, '0');
        });
    }
    tick();
    setInterval(tick, 1000);
}

function refreshCartOffcanvas() {
    fetch('/panier/offcanvas-fragment')
        .then((response) => response.text())
        .then((html) => {
            const body = document.querySelector('[data-cart-offcanvas-body]');
            if (body) {
                body.innerHTML = html;
            }
        });
}

// Cœur (liste de souhaits) : présent sur toutes les cartes produit de l'accueil, des campagnes, etc.
document.addEventListener('DOMContentLoaded', () => {
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-add-to-wishlist]');
        if (!btn) return;

        e.preventDefault();
        const productId = btn.dataset.addToWishlist;

        fetch(`/wishlist/basculer-ajax/${productId}`, { method: 'POST' })
            .then((response) => response.json())
            .then((data) => {
                if (data.redirect) {
                    window.location.href = data.redirect;
                    return;
                }
                if (data.limitReached) {
                    showWishlistLimitModal();
                    return;
                }
                if (!data.success) return;

                // Avant toute chose : efface l'état "groupe ouvert" sur TOUTES les cartes — sans
                // ça, chaque carte précédemment ouverte par un retrait de souhait restait ouverte
                // indéfiniment, s'accumulant à chaque nouveau clic.
                document.querySelectorAll('.product-card.is-touch-active, .home-deal-card.is-touch-active').forEach((el) => {
                    el.classList.remove('is-touch-active');
                });

                document.querySelectorAll(`[data-add-to-wishlist="${productId}"]`).forEach((el) => {
                    el.classList.toggle('is-wishlisted', data.added);
                    const icon = el.querySelector('i');
                    if (icon) {
                        icon.classList.toggle('bi-heart', !data.added);
                        icon.classList.toggle('bi-heart-fill', data.added);
                    }

                    // Sur tactile : si on vient d'AJOUTER aux souhaits, le cœur rouge doit rester
                    // seul (on referme le groupe pour cacher panier/loupe). Si on vient de RETIRER,
                    // on garde le groupe ouvert pour montrer les 3 icônes redevenues normales.
                    const card = el.closest('.product-card, .home-deal-card');
                    if (card) {
                        card.classList.toggle('is-touch-active', !data.added);
                    }
                });
                document.querySelectorAll(`[data-wishlist-indicator="${productId}"]`).forEach((el) => {
                    el.hidden = !data.added;
                });
            });
    });
});

function showWishlistLimitModal() {
    let overlay = document.getElementById('wishlistLimitOverlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.id = 'wishlistLimitOverlay';
        overlay.className = 'wishlist-limit-overlay';
        overlay.innerHTML = `
            <div class="wishlist-limit-modal">
                <p>Le nombre maximum d'articles dans votre liste de souhaits a été atteint (20). Retirez-en un pour en ajouter un nouveau.</p>
                <button type="button" class="wishlist-limit-close">Compris</button>
            </div>
        `;
        document.body.appendChild(overlay);
        overlay.querySelector('.wishlist-limit-close').addEventListener('click', () => overlay.remove());
        overlay.addEventListener('click', (e) => { if (e.target === overlay) overlay.remove(); });
    }
}

// Sur tactile, :hover reste bloqué indéfiniment après un tap (comportement des navigateurs
// mobiles, pas un bug). On gère donc l'ouverture/fermeture des icônes explicitement :
// un tap sur la carte les révèle, un tap ailleurs les referme.
document.addEventListener('DOMContentLoaded', () => {
    if (window.matchMedia('(hover: hover) and (pointer: fine)').matches) return; // ordinateur : rien à faire, :hover suffit

    document.addEventListener('click', (e) => {
        const actionBtn = e.target.closest('[data-add-to-cart], [data-add-to-wishlist], .card-action--expandable');
        const card = e.target.closest('.product-card, .home-deal-card');

        if (actionBtn) return; // un tap sur une icône exécute son action, ne fait que la refermer ensuite
        document.querySelectorAll('.product-card.is-touch-active, .home-deal-card.is-touch-active').forEach((el) => {
            if (el !== card) el.classList.remove('is-touch-active');
        });
        if (card) card.classList.toggle('is-touch-active');
    }, true);

    // Après un tap sur le panier (confirmation via la modale), on referme la carte.
    // Le cœur, lui, garde le groupe ouvert : on veut voir immédiatement le nouvel état
    // (redevenu Panier/Loupe/Cœur normal, ou réduit au cœur rouge épinglé).
    document.addEventListener('click', (e) => {
        const cartBtn = e.target.closest('[data-add-to-cart]');
        if (!cartBtn) return;
        const card = e.target.closest('.product-card, .home-deal-card');
        if (card) card.classList.remove('is-touch-active');
    });
});

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-recently-viewed-widget]').forEach((track) => {
        const pages = Array.from(track.querySelectorAll('.recently-viewed-page'));
        if (pages.length <= 1) return;

        const body = track.parentElement; // .recently-viewed-widget-body, overflow:hidden
        let current = 0;
        let containerWidth = 0;

        const head = body.previousElementSibling;
        const prevBtn = head ? head.querySelector('[data-recently-viewed-prev]') : null;
        const nextBtn = head ? head.querySelector('[data-recently-viewed-next]') : null;

        // Mesure la vraie largeur intérieure disponible (padding déjà déduit par clientWidth),
        // et fixe chaque largeur en pixels exacts — plus aucun calcul en pourcentage, donc
        // plus aucune place pour l'écart qu'on n'arrivait pas à localiser.
        function measure() {
            containerWidth = body.clientWidth;
            pages.forEach((page) => { page.style.width = containerWidth + 'px'; });
            track.style.width = (containerWidth * pages.length) + 'px';
            applyPosition();
        }

        function applyPosition() {
            track.style.transform = `translateX(-${current * containerWidth}px)`;
        }

        function show(index) {
            current = ((index % pages.length) + pages.length) % pages.length;
            applyPosition();
        }

        if (prevBtn) prevBtn.addEventListener('click', () => show(current - 1));
        if (nextBtn) nextBtn.addEventListener('click', () => show(current + 1));

        window.addEventListener('resize', measure);
        measure();
        window.addEventListener('load', measure); // re-mesure une fois tout chargé (polices, images) —
                                                     // certains navigateurs stabilisent la mise en page
                                                     // un peu après DOMContentLoaded, pas au même instant.
    });
});