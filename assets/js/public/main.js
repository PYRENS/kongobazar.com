/* ==========================================================================
   KongoBazar — JS du site public (site-front)
   Vanilla JS uniquement, pas de jQuery (allègement mobile-first).
   Sections : header au scroll, dropdown Site Setting, accordéon catégories,
   hero carousel (bande de progression type "stories").
   ========================================================================== */
document.addEventListener('DOMContentLoaded', () => {
    initHeaderScroll();
    initSiteSettingDropdown();
    initDrillMenu();
    initHeroCarousel();
});

/* --------------------------------------------------------------------------
   Header condensé au scroll + bouton retour en haut
   -------------------------------------------------------------------------- */
function initHeaderScroll() {
    const condensedHeader = document.getElementById('site-header-condensed');
    const backToTop = document.getElementById('back-to-top');
    if (!condensedHeader || !backToTop) return;

    const SCROLL_THRESHOLD = 200;
    let ticking = false;

    function onScroll() {
        const scrollY = window.scrollY;
        condensedHeader.classList.toggle('visible', scrollY > SCROLL_THRESHOLD);
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

    const slides = carousel.querySelectorAll('.hero-slide');
    const fill = carousel.querySelector('#heroProgressFill');
    if (slides.length === 0 || !fill) return;

    const SLIDE_DURATION = 5000; // 5 secondes par slide
    let currentIndex = 0;
    let timer = null;
    let isPaused = false;

    function resetFill() {
        fill.classList.remove('filling');
        fill.style.transitionDuration = '0ms';
        fill.style.width = '0%';
    }

    function playFill() {
        // Double requestAnimationFrame : garantit que le navigateur applique bien le width:0%
        // avant de relancer la transition vers 100%, sinon elle peut être ignorée dans le même frame.
        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                fill.style.transitionDuration = `${SLIDE_DURATION}ms`;
                fill.classList.add('filling');
                fill.style.width = '100%';
            });
        });
    }

    function goToSlide(index) {
        slides[currentIndex].classList.remove('active');
        slides[currentIndex].setAttribute('aria-hidden', 'true');

        currentIndex = index;

        slides[currentIndex].classList.add('active');
        slides[currentIndex].setAttribute('aria-hidden', 'false');

        resetFill();
        playFill();
    }

    function next() {
        goToSlide((currentIndex + 1) % slides.length);
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

    playFill();
    startAutoplay();
}