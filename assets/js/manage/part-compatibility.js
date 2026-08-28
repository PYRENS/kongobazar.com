window.initPartCompatibilityWidget = function () {
    const toggleBtn = document.getElementById('toggleCompatBtn');
    if (!toggleBtn) return;

    const textBlock = document.getElementById('compatTextBlock');
    const rawText = document.getElementById('compatRawText');
    const analyzeBtn = document.getElementById('compatAnalyzeBtn');
    const errorsBox = document.getElementById('compatErrors');
    const resultBox = document.getElementById('compatResult');
    const selectedList = document.getElementById('compatSelectedList');

    toggleBtn.addEventListener('click', () => {
        textBlock.style.display = textBlock.style.display === 'none' ? 'block' : 'none';
    });

    const toggleCompatListBtn = document.getElementById('toggleCompatListBtn');
    if (toggleCompatListBtn) {
        toggleCompatListBtn.addEventListener('click', function () {
            const hidden = selectedList.style.display === 'none';
            selectedList.style.display = hidden ? '' : 'none';
            this.innerHTML = hidden
                ? this.innerHTML.replace('Afficher', 'Masquer').replace('<i class="bi bi-chevron-down"></i>', '<i class="bi bi-chevron-up"></i>')
                : this.innerHTML.replace('Masquer', 'Afficher').replace('<i class="bi bi-chevron-up"></i>', '<i class="bi bi-chevron-down"></i>');
        });
    }

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

    function addBadge(engineId, label) {
        if (selectedList.querySelector('[data-compat-badge="' + engineId + '"]')) return;
        const span = document.createElement('span');
        span.className = 'badge bg-secondary me-1 mb-1';
        span.dataset.compatBadge = engineId;
        span.innerHTML = escapeHtml(label) +
            ' <button type="button" class="btn-close btn-close-white btn-sm" style="font-size:8px;" data-remove-compat="' + engineId + '"></button>' +
            '<input type="hidden" name="compatible_engine_ids[]" value="' + engineId + '">';
        selectedList.appendChild(span);
    }

    selectedList.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-remove-compat]');
        if (btn) btn.closest('[data-compat-badge]').remove();
    });

    analyzeBtn.addEventListener('click', () => {
        errorsBox.style.display = 'none';
        post('/produits/pieces/analyser-compatibilite', { text: rawText.value }).then((result) => {
            render(result);
        });
    });

    function render(result) {
        let html = '';

        const isBlockIdentityResolved = (block) => block.brand.found && block.model.found && block.variant.found;
        const allBlocksIdentityResolved = result.blocks.length > 0 && result.blocks.every(isBlockIdentityResolved);

        if (allBlocksIdentityResolved) {
            html += '<button type="button" id="attachAllGlobalBtn" class="btn btn-sm btn-success mb-2"><i class="bi bi-check2-all"></i> Associer toutes les motorisations (tous les modèles)</button>';
        }

        result.blocks.forEach((block, blockIndex) => {
            const blockIdentityOk = isBlockIdentityResolved(block);
            const isFullyResolved = blockIdentityOk && block.engines.every((e) => e.engineExists);
            const hasUnattachedEngines = block.engines.some((e) => !e.engineExists);

            html += '<div class="card mb-2"><div class="card-body">';
            html += '<div class="d-flex justify-content-between align-items-center" ' + (isFullyResolved ? 'style="cursor:pointer;" data-block-toggle="' + blockIndex + '"' : '') + '>';
            html += '<strong>' + escapeHtml(block.headerPrefix) + (isFullyResolved ? ' <span class="badge bg-success">complet</span>' : '') + '</strong>';
            html += '<span>';
            if (blockIdentityOk && hasUnattachedEngines) {
                html += '<button type="button" class="btn btn-sm btn-outline-success attach-block-btn me-1" data-block="' + blockIndex + '">Associer toutes les motorisations de cette variante</button>';
            }
            if (isFullyResolved) {
                html += '<button type="button" class="btn btn-sm btn-outline-secondary" data-block-toggle-btn="' + blockIndex + '"><i class="bi bi-plus"></i></button>';
            }
            html += '</span>';
            html += '</div>';

            html += '<div data-block-body="' + blockIndex + '" style="' + (isFullyResolved ? 'display:none;' : '') + '" class="mt-2">';

            html += '<div>Marque : <input type="text" class="form-control form-control-sm d-inline-block" style="width:200px;" value="' + escapeHtml(block.brand.name || '') + '" data-brand-input="' + blockIndex + '"> ';
            html += block.brand.found ? '<span class="badge bg-success">trouvée</span>' : ('<span class="badge bg-warning text-dark">non trouvée</span> <button type="button" class="btn btn-sm btn-outline-primary create-brand-btn" data-block="' + blockIndex + '">Créer</button>');
            html += '</div>'

            html += '<div>Modèle : <input type="text" class="form-control form-control-sm d-inline-block" style="width:200px;" value="' + escapeHtml(block.model.name || '') + '" data-model-input="' + blockIndex + '"> ';
            html += block.model.found ? '<span class="badge bg-success">trouvé</span>' : ('<span class="badge bg-warning text-dark">non trouvé</span> ' + (block.brand.found ? '<button type="button" class="btn btn-sm btn-outline-primary create-model-btn" data-block="' + blockIndex + '">Créer</button>' : ''));
            html += '</div>';

            html += '<div>Variante : <input type="text" class="form-control form-control-sm d-inline-block" style="width:200px;" value="' + escapeHtml(block.variant.name || '') + '" data-variant-input="' + blockIndex + '"> ';
            html += block.variant.found ? '<span class="badge bg-success">trouvée</span>' : ('<span class="badge bg-warning text-dark">non trouvée</span> ' + (block.model.found ? '<button type="button" class="btn btn-sm btn-outline-primary create-variant-btn" data-block="' + blockIndex + '">Créer</button>' : ''));
            html += '</div>';

            html += '<table class="table table-sm mt-2"><thead><tr><th>Moteur</th><th>Cylindrée</th><th>CV</th><th>Période</th><th>Statut</th></tr></thead><tbody>';
            block.engines.forEach((engine, engineIndex) => {
                const period = engine.periodBegin.month + '.' + engine.periodBegin.year + ' - ' + (engine.periodEnd ? engine.periodEnd.month + '.' + engine.periodEnd.year : '...');
                html += '<tr>';
                html += '<td>' + escapeHtml(engine.label) + '</td>';
                html += '<td>' + engine.displacementCc + ' ccm</td>';
                html += '<td>' + engine.powerCv + '</td>';
                html += '<td>' + period + '</td>';
                if (engine.engineExists) {
                    html += '<td><span class="badge bg-secondary">déjà associée</span></td>';
                    addBadge(engine.engineId, block.brand.name + ' ' + block.model.name + ' ' + (block.variant.name || '') + ' ' + engine.label);
                } else if (block.variant.found) {
                    html += '<td><button type="button" class="btn btn-sm btn-outline-success create-engine-btn" data-block="' + blockIndex + '" data-engine="' + engineIndex + '">+ Associer</button></td>';
                } else {
                    html += '<td class="text-muted">en attente de la variante</td>';
                }
                html += '</tr>';
            });
            html += '</tbody></table></div></div></div>';
        });

        if (result.unrecognizedLines && result.unrecognizedLines.length > 0) {
            html += '<div class="alert alert-warning"><strong>Lignes non reconnues :</strong><br>' + result.unrecognizedLines.map(escapeHtml).join('<br>') + '</div>';
        }

        resultBox.innerHTML = html;
        attachBlockActions(result);
        attachBlockToggles();
    }

    function attachBlockToggles() {
        resultBox.querySelectorAll('[data-block-toggle]').forEach((header) => {
            header.addEventListener('click', function (e) {
                if (e.target.closest('button') && !e.target.closest('[data-block-toggle-btn]')) return;
                const blockIndex = this.dataset.blockToggle;
                const body = resultBox.querySelector('[data-block-body="' + blockIndex + '"]');
                const icon = resultBox.querySelector('[data-block-toggle-btn="' + blockIndex + '"] i');
                const isHidden = body.style.display === 'none';
                body.style.display = isHidden ? 'block' : 'none';
                if (icon) icon.className = isHidden ? 'bi bi-dash' : 'bi bi-plus';
            });
        });
    }

    function attachBlockActions(result) {
        function createEngineFor(blockIndex, engineIndex) {
            const block = result.blocks[blockIndex];
            const engine = block.engines[engineIndex];
            return post('/produits/pieces/creer-motorisation', {
                variant_id: block.variant.id,
                label: engine.label,
                power_cv: engine.powerCv,
                displacement_cc: engine.displacementCc,
                period_begin: JSON.stringify(engine.periodBegin),
                period_end: JSON.stringify(engine.periodEnd),
            }).then((res) => {
                if (res.id) {
                    addBadge(res.id, block.brand.name + ' ' + block.model.name + ' ' + (block.variant.name || '') + ' ' + engine.label);
                }
            });
        }

        resultBox.querySelectorAll('.attach-block-btn').forEach((btn) => {
            btn.addEventListener('click', function () {
                const blockIndex = this.dataset.block;
                const block = result.blocks[blockIndex];
                const pending = block.engines
                    .map((e, i) => (e.engineExists ? null : i))
                    .filter((i) => i !== null);

                let chain = Promise.resolve();
                pending.forEach((engineIndex) => {
                    chain = chain.then(() => createEngineFor(blockIndex, engineIndex));
                });
                chain.then(() => analyzeBtn.click());
            });
        });

        const globalBtn = document.getElementById('attachAllGlobalBtn');
        if (globalBtn) {
            globalBtn.addEventListener('click', function () {
                let chain = Promise.resolve();
                result.blocks.forEach((block, blockIndex) => {
                    block.engines.forEach((engine, engineIndex) => {
                        if (!engine.engineExists) {
                            chain = chain.then(() => createEngineFor(blockIndex, engineIndex));
                        }
                    });
                });
                chain.then(() => analyzeBtn.click());
            });
        }

        resultBox.querySelectorAll('.create-brand-btn').forEach((btn) => {
            btn.addEventListener('click', function () {
                const blockIndex = this.dataset.block;
                const name = resultBox.querySelector('[data-brand-input="' + blockIndex + '"]').value;
                post('/produits/pieces/creer-marque', { name: name })
                    .then(() => analyzeBtn.click());
            });
        });

        resultBox.querySelectorAll('.create-model-btn').forEach((btn) => {
            btn.addEventListener('click', function () {
                const blockIndex = this.dataset.block;
                const block = result.blocks[blockIndex];
                const name = resultBox.querySelector('[data-model-input="' + blockIndex + '"]').value;
                post('/produits/pieces/creer-modele', { brand_id: block.brand.id, name: name })
                    .then(() => analyzeBtn.click());
            });
        });

        resultBox.querySelectorAll('.create-variant-btn').forEach((btn) => {
            btn.addEventListener('click', function () {
                const blockIndex = this.dataset.block;
                const block = result.blocks[blockIndex];
                const name = resultBox.querySelector('[data-variant-input="' + blockIndex + '"]').value;
                const begin = block.periodBegin || {};
                const end = block.periodEnd || {};
                post('/produits/pieces/creer-variante', {
                    model_id: block.model.id,
                    name: name,
                    month_begin: begin.month || '',
                    year_begin: begin.year || '',
                    month_end: end.month || '',
                    year_end: end.year || '',
                }).then(() => analyzeBtn.click());
            });
        });

        resultBox.querySelectorAll('.create-engine-btn').forEach((btn) => {
            btn.addEventListener('click', function () {
                const blockIndex = this.dataset.block;
                const engineIndex = this.dataset.engine;
                const block = result.blocks[blockIndex];
                const engine = block.engines[engineIndex];
                post('/produits/pieces/creer-motorisation', {
                    variant_id: block.variant.id,
                    label: engine.label,
                    power_cv: engine.powerCv,
                    displacement_cc: engine.displacementCc,
                    period_begin: JSON.stringify(engine.periodBegin),
                    period_end: JSON.stringify(engine.periodEnd),
                }).then((res) => {
                    if (res.id) {
                        addBadge(res.id, block.brand.name + ' ' + block.model.name + ' ' + (block.variant.name || '') + ' ' + engine.label);
                    }
                    analyzeBtn.click();
                });
            });
        });
    }
};