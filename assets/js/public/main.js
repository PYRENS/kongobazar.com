/* ==========================================================================
   KongoBazar — JS du site public (site-front)
   Vanilla JS uniquement, pas de jQuery (allègement mobile-first).
   Sections : header au scroll, dropdown Site Setting, accordéon catégories,
   hero carousel (bande de progression type "stories"), icônes sociales flottantes.
   ========================================================================== */
document.addEventListener('DOMContentLoaded', () => {
    initHeaderScroll();
    initSiteSettingDropdown();
    initDrillMenu();
    initHeroCarousel();
    initSocialFloatPosition();
    initMobileSearchOverlay();
    initMobileHeaderHeight();
    initHeroSideAdsVisibility();
    initMiniCarouselTitleLines();
});

/* --------------------------------------------------------------------------
   Header condensé au scroll + bouton retour en haut
   -------------------------------------------------------------------------- */
function initHeaderScroll() {
    const condensedHeader = document.getElementById('site-header-condensed');
    const backToTop = document.getElementById('back-to-top');
    if (!condensedHeader || !backToTop) return;

    function isMobileLayout() {
        return window.innerWidth <= 991;
    }

    const SCROLL_THRESHOLD = 200;
    let ticking = false;

    function onScroll() {
        const scrollY = window.scrollY;
        // Sur mobile, le header condensé reste affiché en permanence (géré en CSS) —
        // seul le bouton retour en haut suit encore le scroll.
        if (!isMobileLayout()) {
            condensedHeader.classList.toggle('visible', scrollY > SCROLL_THRESHOLD);
        } else {
            condensedHeader.classList.toggle('scrolled', scrollY > SCROLL_THRESHOLD);
        }
        backToTop.classList.toggle('visible', scrollY > SCROLL_THRESHOLD);
        ticking = false;
    }

    window.addEventListener('scroll', () => {
        if (!ticking) {
            window.requestAnimationFrame(onScroll);
            ticking = true;
        }
    });

    backToTop.addEventListener('click', () => {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
}

function initMobileSearchOverlay() {
    const toggle = document.getElementById('mobileSearchToggle');
    const overlay = document.getElementById('mobileSearchOverlay');
    const close = document.getElementById('mobileSearchClose');
    if (!toggle || !overlay || !close) return;

    toggle.addEventListener('click', () => {
        overlay.classList.add('open');
        overlay.querySelector('input').focus();
    });
    close.addEventListener('click', () => {
        overlay.classList.remove('open');
    });
}

/* --------------------------------------------------------------------------
   Dropdown "Site Setting" (langue / devise)
   -------------------------------------------------------------------------- */
function initSiteSettingDropdown() {
    const dropdowns = document.querySelectorAll('.topbar-dropdown');
    if (dropdowns.length === 0) return;

    dropdowns.forEach((dropdown) => {
        const toggle = dropdown.querySelector('.topbar-dropdown-toggle');
        toggle.addEventListener('click', (e) => {
            e.stopPropagation();
            const isOpen = dropdown.classList.contains('open');
            dropdowns.forEach((d) => d.classList.remove('open'));
            if (!isOpen) dropdown.classList.add('open');
        });
    });

    document.addEventListener('click', (e) => {
        dropdowns.forEach((dropdown) => {
            if (!dropdown.contains(e.target)) dropdown.classList.remove('open');
        });
    });
}

function initDrillMenu() {
    document.querySelectorAll('[data-goto-screen]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const targetId = btn.dataset.gotoScreen;
            document.querySelectorAll('.menu-screen.active').forEach((el) => el.classList.remove('active'));
            const target = document.getElementById(targetId);
            if (target) target.classList.add('active');
        });
    });

    const offcanvasEl = document.getElementById('mainMenuOffcanvas');
    if (offcanvasEl) {
        offcanvasEl.addEventListener('hidden.bs.offcanvas', () => {
            document.querySelectorAll('.menu-screen.active').forEach((el) => el.classList.remove('active'));
            const root = document.getElementById('menu-screen-root');
            if (root) root.classList.add('active');
        });
    }
}
/* --------------------------------------------------------------------------
   Hero carousel — bande de progression type "stories", clic pour sauter,
   pause au survol.
   -------------------------------------------------------------------------- */
function initHeroCarousel() {
    const carousel = document.getElementById('heroCarousel');
    if (!carousel) return;

    const track = carousel.querySelector('.hero-carousel-slides');
    const slides = carousel.querySelectorAll('.hero-slide');
    const fill = carousel.querySelector('#heroProgressFill');
    if (slides.length === 0 || !fill || !track) return;

    const SLIDE_DURATION = 5000; // 5 secondes par slide
    const MOBILE_SWIPE_QUERY = '(max-width: 768px)';
    let currentIndex = 0;
    let timer = null;
    let isPaused = false;
    let scrollSyncTimer = null;
    let programmaticScroll = false;

    function isMobileSwipe() {
        return window.matchMedia(MOBILE_SWIPE_QUERY).matches;
    }

    function resetFill() {
        fill.classList.remove('filling');
        fill.style.transitionDuration = '0ms';
        fill.style.width = '0%';
    }

    function playFill() {
        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                fill.style.transitionDuration = `${SLIDE_DURATION}ms`;
                fill.classList.add('filling');
                fill.style.width = '100%';
            });
        });
    }

    const dots = carousel.querySelectorAll('.hero-carousel-dot');

    function setActiveState(index) {
        slides[currentIndex].classList.remove('active');
        slides[currentIndex].setAttribute('aria-hidden', 'true');
        if (dots[currentIndex]) dots[currentIndex].classList.remove('active');

        currentIndex = index;

        slides[currentIndex].classList.add('active');
        slides[currentIndex].setAttribute('aria-hidden', 'false');
        if (dots[currentIndex]) dots[currentIndex].classList.add('active');
    }

    function goToSlide(index) {
        if (isMobileSwipe()) {
            // En mode glissement, on déplace physiquement le scroll au lieu de faire un fondu.
            programmaticScroll = true;
            track.scrollTo({ left: slides[index].offsetLeft, behavior: 'smooth' });
            setActiveState(index);
            window.setTimeout(() => { programmaticScroll = false; }, 500);
            return;
        }
        setActiveState(index);
        resetFill();
        playFill();
    }

    function next() {
        goToSlide((currentIndex + 1) % slides.length);
    }

    function prev() {
        goToSlide((currentIndex - 1 + slides.length) % slides.length);
    }

    function startAutoplay() {
        clearTimeout(timer);
        timer = setTimeout(() => {
            if (!isPaused) next();
            startAutoplay();
        }, SLIDE_DURATION);
    }

    carousel.addEventListener('mouseenter', () => { isPaused = true; });
    carousel.addEventListener('mouseleave', () => { isPaused = false; });
    track.addEventListener('touchstart', () => { isPaused = true; }, { passive: true });
    track.addEventListener('touchend', () => { isPaused = false; }, { passive: true });

    // En glissement tactile, l'utilisateur peut faire défiler au doigt sans cliquer
    // sur une pastille : on resynchronise donc la pastille active sur la position
    // réelle du scroll après chaque geste.
    track.addEventListener('scroll', () => {
        if (!isMobileSwipe() || programmaticScroll) return;
        clearTimeout(scrollSyncTimer);
        scrollSyncTimer = setTimeout(() => {
            let closestIndex = 0;
            let closestDistance = Infinity;
            slides.forEach((slide, i) => {
                const distance = Math.abs(slide.offsetLeft - track.scrollLeft);
                if (distance < closestDistance) {
                    closestDistance = distance;
                    closestIndex = i;
                }
            });
            if (closestIndex !== currentIndex) setActiveState(closestIndex);
        }, 120);
    }, { passive: true });

    dots.forEach((dot) => {
        dot.addEventListener('click', () => {
            const index = parseInt(dot.dataset.slideIndex, 10);
            if (index !== currentIndex) {
                goToSlide(index);
                startAutoplay();
            }
        });
    });

    const prevBtn = carousel.querySelector('.hero-carousel-arrow--prev');
    const nextBtn = carousel.querySelector('.hero-carousel-arrow--next');
    if (prevBtn) prevBtn.addEventListener('click', () => { prev(); startAutoplay(); });
    if (nextBtn) nextBtn.addEventListener('click', () => { next(); startAutoplay(); });

    playFill();
    startAutoplay();
}

/* --------------------------------------------------------------------------
   Icônes sociales flottantes — position calculée dynamiquement sur la
   hauteur réelle du header, au lieu d'un top fixe qui déborde dès que
   le header change de hauteur (bandeau Tendances, etc.).
   -------------------------------------------------------------------------- */
function initSocialFloatPosition() {
    const header = document.getElementById('site-header');
    if (!header) return;

    function updatePosition() {
        document.documentElement.style.setProperty('--social-float-top', header.getBoundingClientRect().height + 'px');
    }

    updatePosition();
    window.addEventListener('resize', updatePosition);
    window.addEventListener('load', updatePosition);
}

/* --------------------------------------------------------------------------
   Pubs latérales du hero — masquées en dessous d'une largeur réglable
   en admin (/hero-pubs-laterales), lue via un attribut data- sur l'élément.
   -------------------------------------------------------------------------- */
function initHeroSideAdsVisibility() {
    const sideAds = document.querySelector('.hero-side-ads');
    if (!sideAds) return;

    const threshold = parseInt(sideAds.dataset.hideBelowWidth, 10);
    if (!threshold) return; // 0 ou vide = jamais masqué

    function update() {
        sideAds.style.display = window.innerWidth <= threshold ? 'none' : '';
    }

    update();
    window.addEventListener('resize', update);
}

function initMiniCarouselTitleLines() {
    function update() {
        document.querySelectorAll('.home-mini-carousel-head').forEach((head) => {
            const title = head.querySelector('h6');
            if (!title) return;
            head.style.setProperty('--title-line-width', title.offsetWidth + 'px');
        });
    }

    update();
    window.addEventListener('resize', update);
    window.addEventListener('load', update);
}


/* --------------------------------------------------------------------------
   Hauteur du header mobile condensé — calculée dynamiquement (logo/icônes
   + bande "Top Rayons" + bandeau Tendances) pour compenser sa position
   fixed sans chevaucher le contenu de la page, y compris après le repli
   de Tendances/Top Rayons au scroll.
   -------------------------------------------------------------------------- */
function initMobileHeaderHeight() {
    const condensedHeader = document.getElementById('site-header-condensed');
    if (!condensedHeader) return;

    function updateHeight() {
        if (window.innerWidth <= 991) {
            document.documentElement.style.setProperty('--mobile-header-height', condensedHeader.getBoundingClientRect().height + 'px');
        }
    }

    updateHeight();
    window.addEventListener('resize', updateHeight);
    window.addEventListener('load', updateHeight);
    window.addEventListener('scroll', updateHeight);

    // Filet de sécurité : si la hauteur du header change pour une autre raison
    // (police qui finit de charger, image qui pousse le contenu, etc.) avant
    // le premier scroll/resize, la variable ne reste pas périmée.
    if (window.ResizeObserver) {
        new ResizeObserver(updateHeight).observe(condensedHeader);
    }
}