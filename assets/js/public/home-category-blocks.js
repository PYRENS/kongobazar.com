/* ==========================================================================
   Blocs catégorie de l'accueil — changement d'onglet tri (Meilleures ventes /
   Nouveaux articles / Vedettes / Tendance) ET changement de sous-catégorie
   dans le menu de gauche, tous deux en AJAX (aucun rechargement de page).
   ========================================================================== */
/**
 * Déplace `el` en première position de son conteneur, avec une animation de
 * glissement fluide (technique FLIP) — uniquement utile/activé en mode mobile
 * glissable (≤991px), pour que l'élément cliqué reste toujours visible sans
 * avoir à re-scroller vers lui à chaque fois.
 */
function moveToFirstWithSlide(container, el, scrollContainer) {
    if (window.innerWidth > 991) return;

    if (container.firstElementChild !== el) {
        const before = el.getBoundingClientRect();
        container.insertBefore(el, container.firstElementChild);
        const after = el.getBoundingClientRect();

        const deltaX = before.left - after.left;
        if (deltaX !== 0) {
            el.style.transition = 'none';
            el.style.transform = 'translateX(' + deltaX + 'px)';
            requestAnimationFrame(() => {
                el.style.transition = 'transform 0.35s ease';
                el.style.transform = 'translateX(0)';
            });
            el.addEventListener('transitionend', () => {
                el.style.transition = '';
                el.style.transform = '';
            }, { once: true });
        }
    }

    // Ramène tout à gauche — y compris le titre de catégorie (ex: "MODE"), qui
    // fait partie de la même barre défilante et peut avoir été poussé hors champ.
    (scrollContainer || container).scrollTo({ left: 0, behavior: 'smooth' });
}
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.home-category-block').forEach((block) => {
        const productsWrap = block.querySelector('.category-block-products');
        if (!productsWrap) return;

        const baseUrl = productsWrap.dataset.productsUrl;
        const sortTabs = block.querySelectorAll('.sort-tab');
        const subcatLinks = block.querySelectorAll('.category-block-subcat-link:not(.category-block-view-all)');

        // L'onglet de tri de départ suit celui déjà marqué "active" par le serveur (premier de l'ordre admin), pas une valeur figée.
        const initialSortTab = block.querySelector('.sort-tab.active');
        let currentSort = initialSortTab ? initialSortTab.dataset.sort : 'best_sellers';
        // Au chargement, la sous-catégorie déjà marquée "active" par le serveur (première de la liste,
        // ou "Voir tous" s'il n'y en a pas) fait foi — pas de valeur figée à null.
        const initialActiveLink = block.querySelector('.category-block-subcat-link.active');
        let currentSubcategory = (initialActiveLink && initialActiveLink.dataset.subcategoryId !== block.dataset.rootCategoryId)
            ? initialActiveLink.dataset.subcategoryId
            : null;

        function refresh() {
            productsWrap.style.opacity = '0.5';
            const params = new URLSearchParams({ sort: currentSort });
            if (currentSubcategory) params.set('subcategory', currentSubcategory);

            fetch(baseUrl + '?' + params.toString())
                .then((r) => r.text())
                .then((html) => {
                    productsWrap.innerHTML = html;
                    productsWrap.style.opacity = '1';
                });
        }

        const sortTabsContainer = block.querySelector('.category-block-sort-tabs');

        sortTabs.forEach((tab) => {
            tab.addEventListener('click', () => {
                sortTabs.forEach((t) => t.classList.remove('active'));
                tab.classList.add('active');
                currentSort = tab.dataset.sort;
                refresh();
                if (sortTabsContainer) moveToFirstWithSlide(sortTabsContainer, tab);
            });
        });

        const subcatList = block.querySelector('.category-block-sidebar ul');
        const subcatScrollBar = block.querySelector('.category-block-sidebar');

        subcatLinks.forEach((link) => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                subcatLinks.forEach((l) => l.classList.remove('active'));
                link.classList.add('active');
                const id = link.dataset.subcategoryId;
                currentSubcategory = (id === block.dataset.rootCategoryId) ? null : id;
                refresh();
                if (subcatList) moveToFirstWithSlide(subcatList, link.parentElement, subcatScrollBar);
            });
        });
    });
});
