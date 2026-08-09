window.initProductAttributePaste = function () {
    const toggleBtn = document.getElementById('toggleAttrPasteBtn');
    if (!toggleBtn) return;

    const textBlock = document.getElementById('attrPasteBlock');
    const rawText = document.getElementById('attrPasteText');
    const analyzeBtn = document.getElementById('attrPasteAnalyzeBtn');
    const resultBox = document.getElementById('attrPasteResult');
    const categoryId = document.getElementById('attrPasteCategoryId').value;
    const fieldsContainer = document.getElementById('attrFieldsContainer');

    fieldsContainer.addEventListener('click', function (e) {
        const clearBtn = e.target.closest('.clear-attr-btn');
        if (!clearBtn) return;

        const field = clearBtn.closest('.d-flex').querySelector('.attr-field');
        if (field) {
            field.value = '';
            field.classList.remove('border-success');
        }
    });

    toggleBtn.addEventListener('click', () => {
        textBlock.style.display = textBlock.style.display === 'none' ? 'block' : 'none';
    });

    function post(url, data) {
        return fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams(data),
        }).then((r) => {
            if (!r.ok) throw new Error('Requête échouée (' + r.status + ')');
            return r.json();
        });
    }

    function escapeHtml(str) {
        const d = document.createElement('div');
        d.textContent = str ?? '';
        return d.innerHTML;
    }

    function fillField(attrId, value, dataType) {
        const field = document.querySelector('[name="attr[' + attrId + ']"]');
        if (!field) return false;

        if ('boolean' === dataType) {
            const normalized = value.trim().toLowerCase();
            field.value = ['oui', 'yes', '1', 'true'].includes(normalized) ? '1' : '0';
        } else if ('select' === dataType) {
            return false;
        } else {
            field.value = value;
        }

        field.closest('.mb-3')?.classList.add('border', 'border-success', 'rounded', 'p-1');
        return true;
    }

    /** Injecte un nouveau champ dans le vrai formulaire, sans recharger la page. */
    function injectNewField(attrId, name, unit, dataType) {
        if (document.querySelector('[name="attr[' + attrId + ']"]')) return;

        const wrapper = document.createElement('div');
        wrapper.className = 'mb-3 d-flex align-items-end gap-2';

        const col = document.createElement('div');
        col.className = 'flex-grow-1';

        const label = document.createElement('label');
        label.className = 'form-label';
        label.textContent = name + (unit ? ' (' + unit + ')' : '');
        col.appendChild(label);

        const input = document.createElement('input');
        input.className = 'form-control attr-field';
        input.name = 'attr[' + attrId + ']';
        input.type = 'number' === dataType ? 'number' : 'text';
        if ('number' === dataType) input.step = '0.001';
        col.appendChild(input);

        wrapper.appendChild(col);

        const clearBtn = document.createElement('button');
        clearBtn.type = 'button';
        clearBtn.className = 'btn btn-sm btn-outline-danger clear-attr-btn';
        clearBtn.title = 'Vider cette caractéristique';
        clearBtn.innerHTML = '<i class="bi bi-x-lg"></i>';
        wrapper.appendChild(clearBtn);

        fieldsContainer.appendChild(wrapper);
    }

    analyzeBtn.addEventListener('click', () => {
        post('/produits/caracteristiques/analyser', { text: rawText.value, category_id: categoryId })
            .then((result) => render(result))
            .catch((err) => {
                resultBox.innerHTML = '<div class="alert alert-danger">Erreur lors de l\'analyse : ' + escapeHtml(err.message) + '</div>';
            });
    });

    let lastResult = null;

    function render(result) {
        lastResult = result;
        const missingCount = result.items.filter((i) => !i.matched.found).length;

        let html = '';
        if (missingCount > 1) {
            html += '<button type="button" id="createAllAttrBtn" class="btn btn-sm btn-success mb-2"><i class="bi bi-check2-all"></i> Créer toutes les caractéristiques manquantes (' + missingCount + ')</button>';
        }

        html += '<table class="table table-sm"><thead><tr><th>Caractéristique</th><th>Unité</th><th>Valeur</th><th>Statut</th></tr></thead><tbody>';

        result.items.forEach((item, index) => {
            html += '<tr>';
            html += '<td>' + escapeHtml(item.name) + '</td>';
            html += '<td>' + escapeHtml(item.unit || '—') + '</td>';
            html += '<td>' + escapeHtml(item.value) + '</td>';

            if (item.matched.found) {
                const filled = fillField(item.matched.id, item.value, item.matched.dataType);
                html += '<td>' + (filled ? '<span class="badge bg-success">rempli</span>' : '<span class="badge bg-warning text-dark">liste — à choisir manuellement</span>') + '</td>';
            } else {
                html += '<td><button type="button" class="btn btn-sm btn-outline-primary create-attr-btn" data-index="' + index + '">+ Créer cette caractéristique</button></td>';
            }
            html += '</tr>';
        });

        html += '</tbody></table>';

        if (result.unrecognizedLines && result.unrecognizedLines.length > 0) {
            html += '<div class="alert alert-warning"><strong>Lignes non reconnues :</strong><br>' + result.unrecognizedLines.map(escapeHtml).join('<br>') + '</div>';
        }

        resultBox.innerHTML = html;
    }

    function createOne(item) {
        return post('/produits/caracteristiques/creer', {
            category_id: categoryId,
            name: item.name,
            unit: item.unit || '',
            sample_value: item.value,
        }).then((res) => {
            if (res.id) {
                injectNewField(res.id, item.name, item.unit, res.dataType);
                fillField(res.id, item.value, res.dataType);
            }
            return res;
        });
    }

    // Délégation d'événements : fonctionne même après que resultBox.innerHTML soit régénéré.
    resultBox.addEventListener('click', function (e) {
        const createBtn = e.target.closest('.create-attr-btn');
        if (createBtn) {
            const item = lastResult.items[createBtn.dataset.index];
            createBtn.disabled = true;
            createBtn.textContent = 'Création...';
            createOne(item)
                .then(() => {
                    createBtn.closest('tr').querySelector('td:last-child').innerHTML = '<span class="badge bg-success">créée et remplie</span>';
                })
                .catch((err) => {
                    createBtn.disabled = false;
                    createBtn.textContent = '+ Créer cette caractéristique';
                    alert('Erreur : ' + err.message);
                });
            return;
        }

        const createAllBtn = e.target.closest('#createAllAttrBtn');
        if (createAllBtn) {
            createAllBtn.disabled = true;
            const missing = lastResult.items.filter((i) => !i.matched.found);

            let chain = Promise.resolve();
            missing.forEach((item) => {
                chain = chain.then(() => createOne(item));
            });
            chain
                .then(() => {
                    createAllBtn.textContent = 'Toutes créées ✓';
                })
                .catch((err) => {
                    createAllBtn.disabled = false;
                    alert('Erreur : ' + err.message);
                });
        }
    });
};