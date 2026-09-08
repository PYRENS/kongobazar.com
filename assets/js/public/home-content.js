/* ==========================================================================
   KongoBazar — JS du contenu de la page d'accueil
   Vanilla JS, sans dépendance.
   ========================================================================== */
document.addEventListener('DOMContentLoaded', () => {
    regroupMiniCarouselForMobileGrid('bestSellersMiniCarousel');
    regroupMiniCarouselForMobileGrid('newArrivalsMiniCarousel');
    initMiniCarousels();
    initMiniCarouselMobileScroll('bestSellersMiniCarousel');
    initMiniCarouselMobileScroll('newArrivalsMiniCarousel');
    initDealsCarousel();
    shortenDealCurrencyOnSmallScreens();
    initTrendingTabs();
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
    const GAP = window.innerWidth <= 496 ? 10 : 20;

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

        function layout() {
            itemsPerView = Math.min(cards.length, 2);
            cardWidth = (viewport.offsetWidth - GAP * (itemsPerView - 1)) / itemsPerView;
            cards.forEach((card) => {
                card.style.width = cardWidth + 'px';
            });
            currentIndex = Math.min(currentIndex, Math.max(0, cards.length - itemsPerView));
            applyPosition(false);
        }

        function applyPosition(animate) {
            track.style.transition = animate ? 'margin-left 0.4s ease' : 'none';
            track.style.marginLeft = `-${currentIndex * (cardWidth + GAP)}px`;
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
            const basePosition = -currentIndex * (cardWidth + GAP);
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
function regroupMiniCarouselForMobileGrid(carouselId) {
    const carousel = document.getElementById(carouselId);
    if (!carousel || window.innerWidth > 991) return;
    if (carousel.dataset.mobileGridDone === '1') return;

    const track = carousel.querySelector('.home-mini-carousel-track');
    const dotsWrap = carousel.querySelector('.home-mini-carousel-dots');
    if (!track) return;

    const products = Array.from(track.querySelectorAll('.home-mini-product'));
    if (products.length === 0) return;

    const GROUP_SIZE = 6;
    const groups = [];
    for (let i = 0; i < products.length; i += GROUP_SIZE) {
        groups.push(products.slice(i, i + GROUP_SIZE));
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

    carousel.dataset.mobileGridDone = '1';
}

function initMiniCarouselMobileScroll(carouselId) {
    const carousel = document.getElementById(carouselId);
    if (!carousel || carousel.dataset.mobileGridDone !== '1') return;

    const track = carousel.querySelector('.home-mini-carousel-track');
    const dots = carousel.querySelectorAll('.dot');
    const pages = track ? track.querySelectorAll('.home-mini-carousel-page') : [];
    if (!track || pages.length === 0) return;

    let currentIndex = 0;
    let syncTimer = null;

    function setActiveDot(index) {
        if (dots[currentIndex]) dots[currentIndex].classList.remove('active');
        currentIndex = index;
        if (dots[currentIndex]) dots[currentIndex].classList.add('active');
    }

    dots.forEach((dot, i) => {
        dot.addEventListener('click', () => {
            track.scrollTo({ left: pages[i].offsetLeft, behavior: 'smooth' });
        });
    });

    track.addEventListener('scroll', () => {
        clearTimeout(syncTimer);
        syncTimer = setTimeout(() => {
            let closest = 0;
            let closestDist = Infinity;
            pages.forEach((p, i) => {
                const d = Math.abs(p.offsetLeft - track.scrollLeft);
                if (d < closestDist) { closestDist = d; closest = i; }
            });
            if (closest !== currentIndex) setActiveDot(closest);
        }, 120);
    }, { passive: true });
}

function initMiniCarousels() {
    const AUTOPLAY_DELAY = 4000;
    document.querySelectorAll('[data-mini-carousel]').forEach((carousel) => {
        if (carousel.dataset.mobileGridDone === '1') return;
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
    return parseInt(panel.dataset.desktopPerPage || '8', 10);
}

function initTrendingPanel(panel) {
    if (!panel._trendingAllCards) {
        panel._trendingAllCards = Array.from(panel.querySelectorAll('.product-card'));
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
        grid.className = 'home-product-grid';
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
function initAddToCartButtons() {
    document.querySelectorAll('[data-add-to-cart]').forEach((btn) => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const variantId = btn.dataset.addToCart;
            fetch(`/panier/ajouter-ajax/${variantId}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'quantity=1',
            })
                .then((response) => response.json())
                .then((data) => {
                    if (data.success) {
                        document.querySelectorAll('[data-cart-count]').forEach((el) => {
                            el.textContent = data.itemCount;
                        });
                        refreshCartOffcanvas();
                    }
                });
        });
    });
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