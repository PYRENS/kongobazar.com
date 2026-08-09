window.initProductAttributeDuplicate = function () {
    const toggleBtn = document.getElementById('toggleAttrDuplicateBtn');
    if (!toggleBtn) return;

    const block = document.getElementById('attrDuplicateBlock');
    const searchInput = document.getElementById('attrDuplicateSearchInput');
    const searchResults = document.getElementById('attrDuplicateSearchResults');
    const checklist = document.getElementById('attrDuplicateChecklist');
    const categoryId = document.getElementById('attrPasteCategoryId').value;
    const currentProductId = document.getElementById('attrDuplicateProductId').value;

    toggleBtn.addEventListener('click', () => {
        block.style.display = block.style.display === 'none' ? 'block' : 'none';
    });

    function escapeHtml(str) {
        const d = document.createElement('div');
        d.textContent = str ?? '';
        return d.innerHTML;
    }

    function fillField(attrId, rawValue) {
        const field = document.querySelector('[name="attr[' + attrId + ']"]');
        if (!field || null === rawValue || undefined === rawValue) return;
        field.value = rawValue;
        field.closest('.mb-3')?.classList.add('border', 'border-success', 'rounded', 'p-1');
    }

    let searchTimeout;
    searchInput.addEventListener('input', function () {
        clearTimeout(searchTimeout);
        const term = this.value.trim();
        if (term.length < 2) {
            searchResults.innerHTML = '';
            return;
        }
        searchTimeout = setTimeout(() => {
            const url = '/produits/caracteristiques/produits-recherche?category_id=' + categoryId
                + '&q=' + encodeURIComponent(term)
                + (currentProductId ? '&exclude=' + currentProductId : '');
            fetch(url).then((r) => r.json()).then((data) => {
                searchResults.innerHTML = '';
                data.results.forEach((prod) => {
                    const item = document.createElement('button');
                    item.type = 'button';
                    item.className = 'list-group-item list-group-item-action';
                    item.textContent = prod.label;
                    item.addEventListener('click', () => {
                        searchInput.value = prod.label;
                        searchResults.innerHTML = '';
                        loadSourceValues(prod.id);
                    });
                    searchResults.appendChild(item);
                });
            });
        }, 300);
    });

    function loadSourceValues(productId) {
        fetch('/produits/caracteristiques/produit-source?product_id=' + productId)
            .then((r) => r.json())
            .then((data) => renderChecklist(data.items));
    }

    function renderChecklist(items) {
        if (items.length === 0) {
            checklist.innerHTML = '<p class="text-muted">Ce produit n\'a aucune caractéristique renseignée.</p>';
            return;
        }

        let html = '';
        items.forEach((item, index) => {
            html += '<div class="form-check">';
            html += '<input type="checkbox" class="form-check-input" id="dupAttr' + index + '" checked data-index="' + index + '">';
            html += '<label class="form-check-label" for="dupAttr' + index + '">' + escapeHtml(item.name) + ' : <strong>' + escapeHtml(item.displayValue) + '</strong></label>';
            html += '</div>';
        });
        html += '<button type="button" id="applyDuplicateBtn" class="btn btn-sm btn-success mt-2">Appliquer la sélection</button>';

        checklist.innerHTML = html;

        document.getElementById('applyDuplicateBtn').addEventListener('click', function () {
            checklist.querySelectorAll('input[type="checkbox"]:checked').forEach((cb) => {
                const item = items[parseInt(cb.dataset.index, 10)];
                fillField(item.attributeId, item.rawValue);
            });
            block.style.display = 'none';
        });
    }
};