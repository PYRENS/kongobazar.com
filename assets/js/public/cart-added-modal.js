/* ==========================================================================
   Modale globale "Ajouté au panier" — se déclenche depuis n'importe quel
   bouton [data-add-to-cart] du site, sur n'importe quelle page.
   Remplace/complète l'ancien handler simple dans home-content.js : ici on
   utilise l'endpoint par PRODUIT (résolution automatique de variante),
   adapté aux cartes qui ne proposent pas de sélection couleur/taille.
   ========================================================================== */
document.addEventListener('DOMContentLoaded', () => {
    const overlay = document.getElementById('cartAddedModalOverlay');
    const image = overlay ? document.getElementById('cartAddedImage') : null;
    const title = overlay ? document.getElementById('cartAddedTitle') : null;
    const qtyLine = overlay ? document.getElementById('cartAddedQtyLine') : null;
    const totalQtyLine = overlay ? document.getElementById('cartAddedTotalQtyLine') : null;
    const closeBtn = overlay ? document.getElementById('cartAddedCloseBtn') : null;
    const continueBtn = overlay ? document.getElementById('cartAddedContinueBtn') : null;

    function openModal(data) {
        if (!overlay) return; // Modale absente sur cette page : l'ajout au panier fonctionne quand même, juste sans confirmation visuelle.
        image.src = data.imageUrl || '';
        title.textContent = data.title;
        qtyLine.innerHTML = data.quantityInCart + ' × <strong>' + data.price + ' ' + data.currency + '</strong>';
        totalQtyLine.textContent = '';

        const subtotalEl = document.getElementById('cartAddedSubtotal');
        const itemCountEl = document.getElementById('cartAddedItemCount');
        if (subtotalEl) subtotalEl.textContent = data.subtotalUsd + ' USD';
        if (itemCountEl) itemCountEl.textContent = 'Votre panier contient ' + data.itemCount + ' article' + (data.itemCount != 1 ? 's' : '');

        overlay.hidden = false;
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        if (!overlay) return;
        overlay.hidden = true;
        document.body.style.overflow = '';
    }

    if (overlay) {
        closeBtn.addEventListener('click', closeModal);
        continueBtn.addEventListener('click', closeModal);
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) closeModal();
        });
    }

    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-add-to-cart]');
        if (!btn) return;

        e.preventDefault();
        const productId = btn.dataset.addToCart;

        fetch(`/panier/ajouter-produit-ajax/${productId}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'quantity=1',
        })
            .then((response) => response.json().then((data) => ({ ok: response.ok, data })))
            .then(({ ok, data }) => {
                if (!ok || !data.success) {
                    if (data.redirect) {
                        window.location.href = data.redirect;
                    }
                    return;
                }

                document.querySelectorAll('[data-cart-count]').forEach((el) => {
                    el.textContent = data.itemCount;
                });
                if (typeof refreshCartOffcanvas === 'function') {
                    refreshCartOffcanvas();
                }

                openModal({
                    imageUrl: data.product.imageUrl,
                    title: data.product.title,
                    price: data.product.price,
                    currency: data.product.currency,
                    quantityAdded: data.product.quantityAdded,
                    quantityInCart: data.product.quantityInCart,
                    subtotalUsd: data.subtotalUsd,
                    itemCount: data.itemCount,
                });
            });
    });
});
