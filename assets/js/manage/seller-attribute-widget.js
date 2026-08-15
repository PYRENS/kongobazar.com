window.initSellerAttributeWidget = function () {
    const searchInput = document.getElementById('sellerAttrSearchInput');
    if (!searchInput) return;

    const searchResults = document.getElementById('sellerAttrSearchResults');
    const fieldsContainer = document.getElementById('sellerAttrFieldsContainer');
    const categoryId = window.sellerAttrCategoryId;

    function post(url, data) {
        return fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams(data),
        }).then((r) => r.json());
    }

    function escapeHtml(str) {
        const d = document.createElement('div');
        d.textContent = str ?? '';
        return d.innerHTML;
    }

    function fieldExists(attrId) {
        return !!document.querySelector('[name="attr[' + attrId + ']"]');
    }

    function injectField(attrId, name, unit, dataType, options) {
        if (fieldExists(attrId)) return;

        const row = document.createElement('div');
        row.className = 'mb-2 d-flex align-items-end gap-2';
        row.dataset.attrRow = attrId;

        const col = document.createElement('div');
        col.className = 'flex-grow-1';

        const label = document.createElement('label');
        label.className = 'form-label';
        label.textContent = name + (unit ? ' (' + unit + ')' : '');
        col.appendChild(label);

        let field;
        if ('boolean' === dataType) {
            field = document.createElement('select');
            field.className = 'form-select seller-attr-field';
            field.innerHTML = '<option value="">—</option><option value="1">Oui</option><option value="0">Non</option>';
        } else if ('select' === dataType) {
            field = document.createElement('select');
            field.className = 'form-select seller-attr-field';
            field.innerHTML = '<option value="">— Choisir —</option>' + options.map((o) => '<option value="' + o.id + '">' + escapeHtml(o.label) + '</option>').join('');
        } else {
            field = document.createElement('input');
            field.type = 'number' === dataType ? 'number' : 'text';
            field.className = 'form-control seller-attr-field';
        }
        field.name = 'attr[' + attrId + ']';
        col.appendChild(field);
        row.appendChild(col);

        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'btn btn-sm btn-outline-danger seller-attr-remove';
        removeBtn.innerHTML = '<i class="bi bi-trash"></i>';
        row.appendChild(removeBtn);

        fieldsContainer.appendChild(row);
        return field;
    }

    fieldsContainer.addEventListener('click', function (e) {
        const removeBtn = e.target.closest('.seller-attr-remove');
        if (removeBtn) {
            removeBtn.closest('[data-attr-row]').remove();
        }
    });

    // --- Méthode 1 : recherche + autocomplétion ---
    let searchTimeout;
    searchInput.addEventListener('input', function () {
        clearTimeout(searchTimeout);
        const term = this.value.trim();
        if (term.length < 2) {
            searchResults.innerHTML = '';
            return;
        }
        searchTimeout = setTimeout(() => {
            fetch('/caracteristiques/recherche-globale?q=' + encodeURIComponent(term))
                .then((r) => r.json())
                .then((data) => {
                    searchResults.innerHTML = '';
                    data.results.forEach((c) => {
                        const item = document.createElement('button');
                        item.type = 'button';
                        item.className = 'list-group-item list-group-item-action';
                        item.textContent = c.label;
                        item.addEventListener('click', () => {
                            searchInput.value = '';
                            searchResults.innerHTML = '';
                            post('/caracteristiques/lier', { category_id: categoryId, characteristic_id: c.id })
                                .then((res) => {
                                    if (res.attributeId) {
                                        injectField(res.attributeId, res.name, res.unit, res.dataType, res.options || []);
                                    }
                                });
                        });
                        searchResults.appendChild(item);
                    });
                });
        }, 300);
    });

    document.addEventListener('click', function (e) {
        if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
            searchResults.innerHTML = '';
        }
    });

    // --- Bouton "Nouveau" ---
    const newBtn = document.getElementById('sellerAttrNewBtn');
    const newForm = document.getElementById('sellerAttrNewForm');
    const newCreateBtn = document.getElementById('sellerAttrNewCreateBtn');

    newBtn.addEventListener('click', () => {
        newForm.style.display = newForm.style.display === 'none' ? 'block' : 'none';
    });

    newCreateBtn.addEventListener('click', function () {
        const name = document.getElementById('sellerAttrNewName').value.trim();
        const unit = document.getElementById('sellerAttrNewUnit').value.trim();
        const dataType = document.getElementById('sellerAttrNewType').value;

        if (!name) return;

        post('/caracteristiques/nouvelle-et-lier', { category_id: categoryId, name: name, unit: unit, data_type: dataType })
            .then((res) => {
                if (res.attributeId) {
                    injectField(res.attributeId, res.name, res.unit, res.dataType, []);
                    document.getElementById('sellerAttrNewName').value = '';
                    document.getElementById('sellerAttrNewUnit').value = '';
                    newForm.style.display = 'none';
                }
            });
    });

    // --- Méthode 2 : duplication depuis un autre produit ---
    const dupBtn = document.getElementById('sellerAttrDuplicateBtn');
    const dupForm = document.getElementById('sellerAttrDuplicateForm');
    const dupSearchInput = document.getElementById('sellerAttrDupSearchInput');
    const dupSearchResults = document.getElementById('sellerAttrDupSearchResults');
    const dupWithValue = document.getElementById('sellerAttrDupWithValue');
    const dupChecklist = document.getElementById('sellerAttrDupChecklist');
    const currentProductId = window.location.search.match(/product=(\d+)/)?.[1] || '';

    dupBtn.addEventListener('click', () => {
        dupForm.style.display = dupForm.style.display === 'none' ? 'block' : 'none';
    });

    let dupTimeout;
    dupSearchInput.addEventListener('input', function () {
        clearTimeout(dupTimeout);
        const term = this.value.trim();
        if (term.length < 2) {
            dupSearchResults.innerHTML = '';
            return;
        }
        dupTimeout = setTimeout(() => {
            const url = '/produits/caracteristiques/produits-recherche?category_id=' + categoryId
                + '&q=' + encodeURIComponent(term) + (currentProductId ? '&exclude=' + currentProductId : '');
            fetch(url).then((r) => r.json()).then((data) => {
                dupSearchResults.innerHTML = '';
                data.results.forEach((prod) => {
                    const item = document.createElement('button');
                    item.type = 'button';
                    item.className = 'list-group-item list-group-item-action';
                    item.textContent = prod.label;
                    item.addEventListener('click', () => {
                        dupSearchInput.value = prod.label;
                        dupSearchResults.innerHTML = '';
                        fetch('/produits/caracteristiques/produit-source?product_id=' + prod.id)
                            .then((r) => r.json())
                            .then((srcData) => renderDupChecklist(srcData.items));
                    });
                    dupSearchResults.appendChild(item);
                });
            });
        }, 300);
    });

    function renderDupChecklist(items) {
        if (items.length === 0) {
            dupChecklist.innerHTML = '<p class="text-muted">Aucune caractéristique sur ce produit.</p>';
            return;
        }

        let html = '';
        items.forEach((item, index) => {
            html += '<div class="form-check">';
            html += '<input type="checkbox" class="form-check-input" id="dupItem' + index + '" checked data-index="' + index + '">';
            html += '<label class="form-check-label" for="dupItem' + index + '">' + escapeHtml(item.name) + (item.unit ? ' (' + item.unit + ')' : '') + '</label>';
            html += '</div>';
        });
        html += '<button type="button" id="applyDupBtn" class="btn btn-sm btn-success mt-2">Dupliquer la sélection</button>';
        dupChecklist.innerHTML = html;

        document.getElementById('applyDupBtn').addEventListener('click', function () {
            const withValue = dupWithValue.checked;
            dupChecklist.querySelectorAll('input[type="checkbox"]:checked').forEach((cb) => {
                const item = items[parseInt(cb.dataset.index, 10)];
                const field = injectField(item.attributeId, item.name, item.unit, item.dataType, []) || document.querySelector('[name="attr[' + item.attributeId + ']"]');
                if (withValue && field && null !== item.rawValue && undefined !== item.rawValue) {
                    field.value = item.rawValue;
                }
            });
            dupForm.style.display = 'none';
        });
    }
};