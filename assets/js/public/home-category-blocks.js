/* ==========================================================================
   Blocs catégorie de l'accueil — changement d'onglet tri (Meilleures ventes /
   Nouveaux articles / Vedettes / Tendance) ET changement de sous-catégorie
   dans le menu de gauche, tous deux en AJAX (aucun rechargement de page).
   ========================================================================== */
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.home-category-block').forEach((block) => {
        const productsWrap = block.querySelector('.category-block-products');
        if (!productsWrap) return;

        const baseUrl = productsWrap.dataset.productsUrl;
        const sortTabs = block.querySelectorAll('.sort-tab');
        const subcatLinks = block.querySelectorAll('.category-block-subcat-link');

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

        sortTabs.forEach((tab) => {
            tab.addEventListener('click', () => {
                sortTabs.forEach((t) => t.classList.remove('active'));
                tab.classList.add('active');
                currentSort = tab.dataset.sort;
                refresh();
            });
        });

        subcatLinks.forEach((link) => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                subcatLinks.forEach((l) => l.classList.remove('active'));
                link.classList.add('active');
                const id = link.dataset.subcategoryId;
                currentSubcategory = (id === block.dataset.rootCategoryId) ? null : id;
                refresh();
            });
        });
    });
});
