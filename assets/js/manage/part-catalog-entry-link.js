window.initPartCatalogEntryLink = function () {
    const input = document.getElementById('catalogEntrySearchInput');
    if (!input) return;

    const results = document.getElementById('catalogEntrySearchResults');
    const hiddenInput = document.getElementById('catalogEntryIdInput');
    const summaryWrap = document.getElementById('catalogEntrySummaryWrap');
    const manualWrap = document.getElementById('partManualFieldsWrap');
    const clearBtn = document.getElementById('catalogEntryClearBtn');

    function setGeneralInfoRequired(required) {
        ['title', 'ean', 'reference'].forEach((name) => {
            const field = document.querySelector('[name="' + name + '"]');
            if (field) field.required = required;
        });
    }

    // --- Classification : pré-remplissage + grisage quand une pièce catalogue est liée ---
    function greyOutClassification() {
        const root = document.getElementById('categoryPickerRoot');
        if (!root) return;
        root.querySelectorAll('select').forEach((s) => {
            s.disabled = true;
            s.style.backgroundColor = '#e9ecef';
        });
    }

    function ungreyClassification() {
        const root = document.getElementById('categoryPickerRoot');
        if (!root) return;
        root.querySelectorAll('select').forEach((s) => {
            s.disabled = false;
            s.style.backgroundColor = '';
        });
    }

    function rebuildClassificationCascade(categoryId, ancestorIdsStr) {
        const root = document.getElementById('categoryPickerRoot');
        const categoryIdInput = document.getElementById('categoryIdInput');
        const displayEl = document.getElementById('categorySelectedDisplay');
        if (!root || !categoryIdInput || !categoryId) return;

        root.querySelectorAll('select[data-category-level]').forEach((s) => {
            if (parseInt(s.dataset.categoryLevel, 10) > 1) s.remove();
        });

        categoryIdInput.value = categoryId;

        const ids = (ancestorIdsStr || '').split(',').map((s) => s.trim()).filter(Boolean);
        ids.push(String(categoryId));

        function step(level, remainingIds) {
            if (remainingIds.length === 0) {
                greyOutClassification();
                return;
            }
            const currentId = remainingIds[0];
            const select = root.querySelector('select[data-category-level="' + level + '"]');
            if (select) {
                select.value = currentId;
            }
            if (remainingIds.length === 1) {
                if (select && select.selectedIndex >= 0 && select.options[select.selectedIndex]) {
                    displayEl.textContent = select.options[select.selectedIndex].text;
                }
                greyOutClassification();
                return;
            }
            fetch('/categories/enfants/' + currentId)
                .then((r) => r.json())
                .then((data) => {
                    const newSelect = document.createElement('select');
                    newSelect.dataset.categoryLevel = String(level + 1);
                    newSelect.className = 'form-select';
                    newSelect.style.width = '100%';
                    newSelect.innerHTML = '<option value="">— Choisir —</option>';
                    data.results.forEach((cat) => {
                        const opt = document.createElement('option');
                        opt.value = cat.id;
                        opt.textContent = cat.name;
                        newSelect.appendChild(opt);
                    });
                    root.appendChild(newSelect);
                    step(level + 1, remainingIds.slice(1));
                });
        }

        step(1, ids);
    }

    function applyLink(entry) {
        hiddenInput.value = entry.id;
        input.value = entry.label;
        results.innerHTML = '';
        manualWrap.style.display = 'none';
        setGeneralInfoRequired(false);

        if (entry.categoryId) {
            rebuildClassificationCascade(entry.categoryId, entry.categoryAncestorIds);
        }

        const attrOld = document.getElementById('attrOldSystemWrap');
        const attrFree = document.getElementById('attrFreeSystemWrap');
        if (attrOld) attrOld.style.display = 'none';
        if (attrFree) attrFree.style.display = '';

        fetch('/produits/catalogue-pieces/' + entry.id + '/resume')
            .then((r) => r.text())
            .then((html) => {
                summaryWrap.innerHTML = html;
                summaryWrap.style.display = '';
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.id = 'catalogEntryClearBtn';
                btn.className = 'btn btn-sm btn-outline-danger mt-2';
                btn.innerHTML = '<i class="bi bi-x-lg"></i> Retirer le lien';
                btn.addEventListener('click', clearLink);
                summaryWrap.appendChild(btn);
            });
    }

    function clearLink() {
        hiddenInput.value = '';
        input.value = '';
        summaryWrap.innerHTML = '';
        summaryWrap.style.display = 'none';
        manualWrap.style.display = '';
        setGeneralInfoRequired(true);
        ungreyClassification();

        const attrOld = document.getElementById('attrOldSystemWrap');
        const attrFree = document.getElementById('attrFreeSystemWrap');
        if (attrOld) attrOld.style.display = '';
        if (attrFree) attrFree.style.display = 'none';
    }

    let timeout;
    input.addEventListener('input', function () {
        clearTimeout(timeout);
        const term = this.value.trim();
        if (term.length < 2) {
            results.innerHTML = '';
            return;
        }
        timeout = setTimeout(() => {
            fetch('/produits/catalogue-pieces/rechercher?q=' + encodeURIComponent(term))
                .then((r) => r.json())
                .then((data) => {
                    results.innerHTML = '';
                    data.results.forEach((entry) => {
                        const item = document.createElement('button');
                        item.type = 'button';
                        item.className = 'list-group-item list-group-item-action';
                        item.textContent = entry.label;
                        item.addEventListener('click', () => applyLink(entry));
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

    if (clearBtn) clearBtn.addEventListener('click', clearLink);

    // Déjà lié à l'arrivée sur la page (édition) : "Informations générales" optionnel + Classification grisée.
    if (hiddenInput.value) {
        setGeneralInfoRequired(false);
        greyOutClassification();
        const attrOld = document.getElementById('attrOldSystemWrap');
        const attrFree = document.getElementById('attrFreeSystemWrap');
        if (attrOld) attrOld.style.display = 'none';
        if (attrFree) attrFree.style.display = '';
    }
};