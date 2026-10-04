/* ==========================================================================
   Agrandissement des miniatures d'upload de l'admin (curseur « loupe »).
   Commun à tout le back-office : toute image dans .admin-upload-thumb-inner,
   ou portant l'attribut data-zoomable, s'ouvre en grand au clic — y compris
   une nouvelle image choisie mais pas encore enregistrée (aperçu local).
   Affiche aussi les dimensions réelles de l'image (utile pour les bannières).
   Fermeture : clic n'importe où, croix, ou touche Échap.
   ========================================================================== */
(function () {
    let overlay = null;

    function buildOverlay() {
        overlay = document.createElement('div');
        overlay.className = 'kb-panel-overlay kb-image-zoom';
        overlay.innerHTML =
            '<div class="kb-image-zoom-box">' +
                '<button type="button" class="kb-image-zoom-close" aria-label="Fermer"><i class="bi bi-x-lg"></i></button>' +
                '<img class="kb-image-zoom-img" src="" alt="">' +
                '<div class="kb-image-zoom-caption"></div>' +
            '</div>';
        document.body.appendChild(overlay);
        overlay.addEventListener('click', close);
    }

    function open(src, alt) {
        if (!overlay) buildOverlay();
        const img = overlay.querySelector('.kb-image-zoom-img');
        const caption = overlay.querySelector('.kb-image-zoom-caption');
        caption.textContent = '';
        img.onload = () => {
            caption.textContent = img.naturalWidth + ' × ' + img.naturalHeight + ' px';
        };
        img.alt = alt || '';
        img.src = src;
        overlay.classList.add('kb-panel-open');
    }

    function close() {
        if (overlay) overlay.classList.remove('kb-panel-open');
    }

    document.addEventListener('click', (e) => {
        const img = e.target.closest('.admin-upload-thumb-inner img, img[data-zoomable]');
        if (!img) return;
        const src = img.getAttribute('src');
        if (!src) return; // miniature vide (aucune image encore choisie)
        e.preventDefault();
        open(src, img.alt);
    });

    document.addEventListener('keydown', (e) => {
        if ('Escape' === e.key) close();
    });
})();