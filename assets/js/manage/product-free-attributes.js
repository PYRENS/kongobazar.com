window.initEmptyAttrsToggle = function () {
    const btn = document.getElementById('toggleEmptyAttrsBtn');
    const wrap = document.getElementById('attrEmptyFieldsContainer');
    if (!btn || !wrap) return;

    btn.addEventListener('click', function () {
        const hidden = wrap.style.display === 'none';
        wrap.style.display = hidden ? '' : 'none';
        this.innerHTML = hidden
            ? '<i class="bi bi-chevron-up"></i> Masquer les champs vides'
            : this.dataset.originalLabel;
    });
    btn.dataset.originalLabel = btn.innerHTML;
};

window.initFreeAttributes = function () {
    const container = document.getElementById('attrRowsContainer');
    const addBtn = document.getElementById('addAttrRowBtn');
    if (!container || !addBtn) return;

    function wireRow(row) {
        const nameInput = row.querySelector('.attr-name-input');
        const hiddenId = row.querySelector('.attr-characteristic-id');
        const hiddenName = row.querySelector('.attr-name-hidden');
        const results = row.querySelector('.attr-name-results');
        const valueInput = row.querySelector('.attr-value-input');
        const removeBtn = row.querySelector('.remove-attr-row');

        valueInput.required = nameInput.value.trim().length > 0;

        let timeout;
        nameInput.addEventListener('input', function () {
            hiddenId.value = '';
            hiddenName.value = this.value;
            valueInput.required = this.value.trim().length > 0;

            clearTimeout(timeout);
            const term = this.value.trim();
            if (term.length < 1) {
                results.innerHTML = '';
                return;
            }
            timeout = setTimeout(() => {
                fetch('/produits/caracteristiques-recherche?q=' + encodeURIComponent(term))
                    .then((r) => r.json())
                    .then((data) => {
                        results.innerHTML = '';
                        data.results.forEach((c) => {
                            const item = document.createElement('button');
                            item.type = 'button';
                            item.className = 'list-group-item list-group-item-action';
                            item.textContent = c.label;
                            item.addEventListener('click', () => {
                                nameInput.value = c.label;
                                hiddenId.value = c.id;
                                hiddenName.value = c.label;
                                results.innerHTML = '';
                                valueInput.required = true;
                                valueInput.focus();
                            });
                            results.appendChild(item);
                        });
                    });
            }, 250);
        });

        document.addEventListener('click', function (e) {
            if (!nameInput.contains(e.target) && !results.contains(e.target)) {
                results.innerHTML = '';
            }
        });

        removeBtn.addEventListener('click', () => row.remove());
    }

    container.querySelectorAll('.attr-row').forEach(wireRow);

    addBtn.addEventListener('click', function () {
        const row = document.createElement('div');
        row.className = 'mb-2 d-flex align-items-center gap-2 attr-row';
        row.innerHTML =
            '<div class="flex-grow-1" style="position:relative;">' +
                '<input type="text" class="form-control attr-name-input" placeholder="Caractéristique" autocomplete="off">' +
                '<input type="hidden" class="attr-characteristic-id" name="attr_characteristic_id[]" value="">' +
                '<input type="hidden" class="attr-name-hidden" name="attr_name[]" value="">' +
                '<div class="list-group attr-name-results" style="position:absolute;z-index:10;width:100%;"></div>' +
            '</div>' +
            '<div class="flex-grow-1">' +
                '<input type="text" class="form-control attr-value-input" name="attr_value[]" placeholder="Valeur">' +
            '</div>' +
            '<button type="button" class="btn btn-sm btn-outline-danger remove-attr-row" title="Supprimer cette ligne"><i class="bi bi-trash"></i></button>';
        container.appendChild(row);
        wireRow(row);
        row.querySelector('.attr-name-input').focus();
    });
};