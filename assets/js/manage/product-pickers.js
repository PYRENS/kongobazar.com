document.addEventListener('DOMContentLoaded', () => {
    initProductCategoryPicker();
    initSellerAutocomplete();
});

function initProductCategoryPicker() {
    const root = document.getElementById('categoryPickerRoot');
    const finalInput = document.getElementById('categoryIdInput');
    if (!root || !finalInput) return;

    const ancestorIds = (finalInput.dataset.ancestorIds || '').split(',').filter(Boolean);
    const rootSelect = root.querySelector('select[data-category-level="1"]');

    rootSelect.addEventListener('change', () => onCategoryPickerChange(rootSelect, root, finalInput, 1));

    if (ancestorIds.length > 0) {
        rootSelect.value = ancestorIds[0];
        loadCategoryPickerLevel(ancestorIds[0], root, finalInput, 2, ancestorIds);
    }
}

function onCategoryPickerChange(select, root, finalInput, level, ancestorIds = []) {
    root.querySelectorAll('select[data-category-level]').forEach((s) => {
        if (parseInt(s.dataset.categoryLevel, 10) > level) s.remove();
    });

    finalInput.value = select.value;
    document.getElementById('categorySelectedDisplay').textContent = select.options[select.selectedIndex] ? select.options[select.selectedIndex].text : '';

    if (window.onProductCategoryChange) window.onProductCategoryChange(select.value);

    if (!select.value) return;
    loadCategoryPickerLevel(select.value, root, finalInput, level + 1, ancestorIds);
}

function loadCategoryPickerLevel(parentId, root, finalInput, level, ancestorIds = []) {
    fetch(`/categories/enfants/${parentId}`)
        .then((r) => r.json())
        .then((data) => {
            if (data.results.length === 0) return;

            const select = document.createElement('select');
            select.dataset.categoryLevel = String(level);
            select.className = 'form-select';
            select.style.width = '100%';
            select.style.maxWidth = 'none';
            select.innerHTML = '<option value="">— Choisir —</option>';
            data.results.forEach((cat) => {
                const opt = document.createElement('option');
                opt.value = cat.id;
                opt.textContent = cat.name;
                select.appendChild(opt);
            });
            root.appendChild(select);

            select.addEventListener('change', () => onCategoryPickerChange(select, root, finalInput, level, ancestorIds));

            const preselect = ancestorIds[level - 1];
            if (preselect) {
                select.value = preselect;
                finalInput.value = preselect;
                document.getElementById('categorySelectedDisplay').textContent = select.options[select.selectedIndex] ? select.options[select.selectedIndex].text : '';
                if (window.onProductCategoryChange) window.onProductCategoryChange(preselect);
                loadCategoryPickerLevel(preselect, root, finalInput, level + 1, ancestorIds);
            }
        });
}

function initSellerAutocomplete() {
    const input = document.getElementById('sellerSearchInput');
    const results = document.getElementById('sellerSearchResults');
    const hiddenInput = document.getElementById('sellerIdInput');
    if (!input) return;

    let timeout;

    input.addEventListener('input', function () {
        clearTimeout(timeout);
        hiddenInput.value = '';
        const term = this.value.trim();
        if (term.length < 2) {
            results.innerHTML = '';
            return;
        }
        timeout = setTimeout(() => {
            fetch('/produits/vendeurs-recherche?q=' + encodeURIComponent(term))
                .then((r) => r.json())
                .then((data) => {
                    results.innerHTML = '';
                    data.results.forEach((seller) => {
                        const item = document.createElement('button');
                        item.type = 'button';
                        item.className = 'list-group-item list-group-item-action';
                        item.textContent = seller.label;
                        item.addEventListener('click', () => {
                            input.value = seller.label;
                            hiddenInput.value = seller.id;
                            results.innerHTML = '';
                        });
                        results.appendChild(item);
                    });
                });
        }, 300);
    });

    document.addEventListener('click', function (e) {
        if (!input.contains(e.target) && !results.contains(e.target)) {
            results.innerHTML = '';
        }
    });
}