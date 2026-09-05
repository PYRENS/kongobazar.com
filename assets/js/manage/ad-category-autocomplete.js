window.initAdCategoryAutocomplete = function () {
    const input = document.getElementById('adCategorySearchInput');
    if (!input) return;

    const results = document.getElementById('adCategorySearchResults');
    const hiddenInput = document.getElementById('adCategoryIdInput');
    const clearBtn = document.getElementById('adCategoryClearBtn');

    let timeout;

    function renderResults(items) {
        results.innerHTML = '';
        if (items.length === 0) {
            results.style.display = 'none';
            return;
        }
        items.forEach((cat) => {
            const item = document.createElement('button');
            item.type = 'button';
            item.className = 'list-group-item list-group-item-action';
            item.textContent = cat.name;
            item.addEventListener('click', () => {
                input.value = cat.name;
                hiddenInput.value = cat.id;
                clearBtn.style.display = 'block';
                results.style.display = 'none';
            });
            results.appendChild(item);
        });
        results.style.display = 'block';
    }

    function search(term) {
        fetch('/categories/recherche-json?q=' + encodeURIComponent(term))
            .then((r) => r.json())
            .then((data) => renderResults(data.results));
    }

    input.addEventListener('input', function () {
        clearTimeout(timeout);
        hiddenInput.value = '';
        clearBtn.style.display = 'none';
        const term = this.value.trim();
        if (term.length < 2) {
            results.style.display = 'none';
            return;
        }
        timeout = setTimeout(() => search(term), 300);
    });

    // Croix : réinitialise complètement le champ
    clearBtn.addEventListener('click', () => {
        input.value = '';
        hiddenInput.value = '';
        clearBtn.style.display = 'none';
        results.style.display = 'none';
        input.focus();
    });

    document.addEventListener('click', function (e) {
        if (!input.closest('.position-relative').contains(e.target)) {
            results.style.display = 'none';
        }
    });

    // Deuxième méthode : parcours par cascade (arborescence niveau par niveau).
    const cascadeToggleBtn = document.getElementById('adCategoryCascadeToggleBtn');
    const cascadeWrap = document.getElementById('adCategoryCascadeWrap');
    const cascadeContainer = document.getElementById('adCategoryCascade');
    let cascadeInitialized = false;

    if (cascadeToggleBtn) {
        cascadeToggleBtn.addEventListener('click', () => {
            const isHidden = cascadeWrap.style.display === 'none';
            cascadeWrap.style.display = isHidden ? 'block' : 'none';
            if (isHidden && !cascadeInitialized) {
                cascadeInitialized = true;
                initCascadeLevel(cascadeContainer, null);
            }
        });
    }

    function initCascadeLevel(container, parentId) {
        fetch(cascadeContainer.dataset.childrenUrl + '?parent_id=' + (parentId || ''))
            .then((r) => r.json())
            .then((data) => {
                if (data.results.length === 0) return;

                const row = document.createElement('div');
                row.className = 'd-flex align-items-center gap-2 mb-2';

                const select = document.createElement('select');
                select.className = 'form-select';
                select.innerHTML = '<option value="">— Choisir —</option>' +
                    data.results.map((c) => `<option value="${c.id}" data-name="${c.name}" data-has-children="${c.hasChildren}">${c.name}</option>`).join('');

                const pickBtn = document.createElement('button');
                pickBtn.type = 'button';
                pickBtn.className = 'btn btn-sm btn-outline-secondary';
                pickBtn.textContent = 'Choisir ce niveau';
                pickBtn.disabled = true;

                select.addEventListener('change', function () {
                    let next = row.nextElementSibling;
                    while (next) {
                        const toRemove = next;
                        next = next.nextElementSibling;
                        toRemove.remove();
                    }
                    pickBtn.disabled = !this.value;
                    if (this.value) {
                        const opt = this.options[this.selectedIndex];
                        if (opt.dataset.hasChildren === 'true') {
                            initCascadeLevel(container, this.value);
                        }
                    }
                });

                pickBtn.addEventListener('click', function () {
                    const opt = select.options[select.selectedIndex];
                    input.value = opt.dataset.name;
                    hiddenInput.value = select.value;
                    clearBtn.style.display = 'block';
                });

                row.appendChild(select);
                row.appendChild(pickBtn);
                container.appendChild(row);
            });
    }
};

document.addEventListener('DOMContentLoaded', () => {
    if (typeof window.initAdCategoryAutocomplete === 'function') {
        window.initAdCategoryAutocomplete();
    }
});