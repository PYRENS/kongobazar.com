document.addEventListener('DOMContentLoaded', () => {
    initCartPageSteppers();
});

function initCartPageSteppers() {
    document.querySelectorAll('[data-cart-line]').forEach((line) => {
        const itemId = line.dataset.itemId;
        const decreaseBtn = line.querySelector('[data-qty-decrease]');
        const increaseBtn = line.querySelector('[data-qty-increase]');
        const qtyValue = line.querySelector('[data-qty-value]');
        const lineTotal = line.querySelector('[data-line-total]');

        let busy = false;

        function sendUpdate(newQuantity) {
            if (busy) return;
            busy = true;

            fetch(`/panier/quantite-ajax/${itemId}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `quantity=${newQuantity}`,
            })
                .then((response) => response.json())
                .then((data) => {
                    if (!data.success) return;

                    updateGlobalCartIndicators(data.itemCount, data.displayAmount, data.displayCurrency);

                    if (data.removed) {
                        line.remove();
                        maybeShowEmptyState();
                        return;
                    }

                    qtyValue.textContent = data.quantity;
                    if (lineTotal) lineTotal.textContent = data.lineTotal;
                })
                .finally(() => { busy = false; });
        }

        if (decreaseBtn) {
            decreaseBtn.addEventListener('click', () => {
                const current = parseInt(qtyValue.textContent, 10);
                sendUpdate(current - 1);
            });
        }

        if (increaseBtn) {
            increaseBtn.addEventListener('click', () => {
                if (increaseBtn.disabled) return;
                const current = parseInt(qtyValue.textContent, 10);
                sendUpdate(current + 1);
            });
        }
    });
}

function updateGlobalCartIndicators(itemCount, displayAmount, displayCurrency) {
    document.querySelectorAll('[data-cart-count]').forEach((el) => {
        el.textContent = itemCount;
    });
    document.querySelectorAll('[data-cart-summary-total]').forEach((el) => {
        el.textContent = displayAmount;
    });

    const offcanvasBody = document.querySelector('[data-cart-offcanvas-body]');
    if (offcanvasBody) {
        fetch('/panier/offcanvas-fragment')
            .then((r) => r.text())
            .then((html) => { offcanvasBody.innerHTML = html; });
    }
}

function maybeShowEmptyState() {
    const remaining = document.querySelectorAll('[data-cart-line]');
    if (remaining.length === 0) {
        window.location.reload(); // Repli simple : réaffiche l'état vide correctement stylé
    }
}