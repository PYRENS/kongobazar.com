document.addEventListener('DOMContentLoaded', () => {
    initProductGallery();
    initProductOptions();
    initProductTabs();
    initProductShare();
});

function initProductGallery() {
    const mainImage = document.querySelector('[data-product-main-image]');
    const thumbs = document.querySelectorAll('.product-thumb');
    if (!mainImage || thumbs.length === 0) return;

    thumbs.forEach((thumb) => {
        thumb.addEventListener('click', () => {
            thumbs.forEach((t) => t.classList.remove('active'));
            thumb.classList.add('active');
            mainImage.setAttribute('src', thumb.dataset.swapImage);
        });
    });
}

function initProductOptions() {
    const page = document.querySelector('.product-page');
    if (!page) return;

    const slug = page.dataset.productSlug;
    const colorButtons = document.querySelectorAll('.color-swatch');
    const sizeButtons = document.querySelectorAll('.size-swatch');
    const stockInfo = document.querySelector('[data-stock-info-text]');
    const stockBadge = document.querySelector('[data-stock-badge]');
    const stockText = document.querySelector('[data-stock-text]');
    const addBtn = document.querySelector('[data-product-add-to-cart]');
    const buyNowBtn = document.querySelector('[data-product-buy-now]');
    const qtyValueEl = document.querySelector('[data-product-qty-value]');
    const qtyDecreaseBtn = document.querySelector('[data-product-qty-decrease]');
    const qtyIncreaseBtn = document.querySelector('[data-product-qty-increase]');
    const wishlistLink = document.querySelector('[data-wishlist-link]');

    let selectedColor = null;
    let selectedSize = null;
    let currentVariantId = null;
    let maxStock = 1;

    const hasColors = colorButtons.length > 0;
    const hasSizes = sizeButtons.length > 0;

    colorButtons.forEach((btn) => {
        btn.addEventListener('click', () => {
            colorButtons.forEach((b) => b.classList.remove('selected'));
            btn.classList.add('selected');
            selectedColor = btn.dataset.colorId;
            checkVariant();
        });
    });

    sizeButtons.forEach((btn) => {
        btn.addEventListener('click', () => {
            sizeButtons.forEach((b) => b.classList.remove('selected'));
            btn.classList.add('selected');
            selectedSize = btn.dataset.sizeId;
            checkVariant();
        });
    });

    function checkVariant() {
        if ((hasColors && !selectedColor) || (hasSizes && !selectedSize)) {
            return;
        }

        const params = new URLSearchParams();
        if (selectedColor) params.set('color', selectedColor);
        if (selectedSize) params.set('size', selectedSize);

        fetch(`/produit-variant/${slug}?${params.toString()}`)
            .then((response) => response.json())
            .then((data) => {
                if (!data.found) {
                    currentVariantId = null;
                    if (addBtn) addBtn.disabled = true;
                    if (buyNowBtn) buyNowBtn.disabled = true;
                    if (stockText) stockText.textContent = 'Combinaison indisponible';
                    if (stockInfo) stockInfo.className = 'product-stock-info out-of-stock';
                    if (stockBadge) stockBadge.innerHTML = '<i class="bi bi-x-circle-fill" style="color:#e53935"></i> Indisponible';
                    return;
                }

                currentVariantId = data.variantId;
                maxStock = data.stock;

                if (wishlistLink) {
                    wishlistLink.href = `/wishlist/ajouter/${data.variantId}`;
                }

                if (data.inStock) {
                    if (addBtn) addBtn.disabled = false;
                    if (buyNowBtn) buyNowBtn.disabled = false;
                    if (stockText) stockText.textContent = `En stock (${data.stock} disponible${data.stock > 1 ? 's' : ''})`;
                    if (stockInfo) stockInfo.className = 'product-stock-info in-stock';
                    if (stockBadge) stockBadge.innerHTML = '<i class="bi bi-check-circle-fill"></i> En stock';
                } else {
                    if (addBtn) addBtn.disabled = true;
                    if (buyNowBtn) buyNowBtn.disabled = true;
                    if (stockText) stockText.textContent = 'Rupture de stock';
                    if (stockInfo) stockInfo.className = 'product-stock-info out-of-stock';
                    if (stockBadge) stockBadge.innerHTML = '<i class="bi bi-x-circle-fill" style="color:#e53935"></i> Rupture de stock';
                }
            });
    }

    if (!hasColors && !hasSizes) {
        checkVariant();
    }

    if (qtyDecreaseBtn && qtyIncreaseBtn && qtyValueEl) {
        qtyDecreaseBtn.addEventListener('click', () => {
            const current = parseInt(qtyValueEl.textContent, 10);
            if (current > 1) qtyValueEl.textContent = current - 1;
        });
        qtyIncreaseBtn.addEventListener('click', () => {
            const current = parseInt(qtyValueEl.textContent, 10);
            if (current < maxStock) qtyValueEl.textContent = current + 1;
        });
    }

    if (addBtn) {
        addBtn.addEventListener('click', () => {
            if (!currentVariantId) return;

            const quantity = qtyValueEl ? parseInt(qtyValueEl.textContent, 10) || 1 : 1;

            fetch(`/panier/ajouter-ajax/${currentVariantId}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `quantity=${quantity}`,
            })
                .then((response) => response.json())
                .then((data) => {
                    if (data.success) {
                        document.querySelectorAll('[data-cart-count]').forEach((el) => {
                            el.textContent = data.itemCount;
                        });
                        const offcanvasBody = document.querySelector('[data-cart-offcanvas-body]');
                        if (offcanvasBody) {
                            fetch('/panier/offcanvas-fragment')
                                .then((r) => r.text())
                                .then((html) => { offcanvasBody.innerHTML = html; });
                        }
                    }
                });
        });
    }

    if (buyNowBtn) {
        buyNowBtn.addEventListener('click', () => {
            if (!currentVariantId) return;
            const quantity = qtyValueEl ? parseInt(qtyValueEl.textContent, 10) || 1 : 1;

            fetch(`/panier/ajouter-ajax/${currentVariantId}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `quantity=${quantity}`,
            }).then(() => {
                window.location.href = '/commande';
            });
        });
    }
}

function initProductTabs() {
    document.querySelectorAll('.product-tab').forEach((tab) => {
        tab.addEventListener('click', () => {
            document.querySelectorAll('.product-tab').forEach((t) => t.classList.remove('active'));
            document.querySelectorAll('.product-tab-panel').forEach((p) => p.classList.remove('active'));
            tab.classList.add('active');
            document.getElementById(tab.dataset.productTabTarget).classList.add('active');
        });
    });
}

function initProductShare() {
    const shareBtn = document.querySelector('[data-share-btn]');
    if (!shareBtn) return;

    shareBtn.addEventListener('click', () => {
        const shareData = {
            title: shareBtn.dataset.shareTitle,
            text: shareBtn.dataset.shareText,
            url: window.location.href,
        };

        if (navigator.share) {
            navigator.share(shareData).catch(() => {
                // L'utilisateur a annulé le partage, on ne fait rien de plus
            });
            return;
        }

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(window.location.href).then(() => {
                showCopiedFeedback(shareBtn);
            }).catch(() => {
                fallbackCopy(window.location.href, shareBtn);
            });
        } else {
            fallbackCopy(window.location.href, shareBtn);
        }
    });
}

function fallbackCopy(text, btn) {
    // Repli compatible HTTP (sans navigator.clipboard) : champ temporaire + document.execCommand
    const tempInput = document.createElement('input');
    tempInput.value = text;
    tempInput.style.position = 'fixed';
    tempInput.style.opacity = '0';
    document.body.appendChild(tempInput);
    tempInput.select();
    tempInput.setSelectionRange(0, 99999);

    try {
        document.execCommand('copy');
        showCopiedFeedback(btn);
    } catch (e) {
        // Dernier repli : rien de cassé, juste pas de confirmation visuelle
    }

    document.body.removeChild(tempInput);
}

function showCopiedFeedback(btn) {
    const originalHTML = btn.innerHTML;
    btn.innerHTML = '<i class="bi bi-check2"></i> Lien copié !';
    setTimeout(() => {
        btn.innerHTML = originalHTML;
    }, 2000);
}