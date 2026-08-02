document.addEventListener('DOMContentLoaded', () => {
    initVehicleModelPicker();
    initVehicleTypeToggle();
});

function initVehicleModelPicker() {
    const brandSelect = document.querySelector('[data-vehicle-brand-select]');
    const modelSelect = document.querySelector('[data-vehicle-model-select]');
    if (!brandSelect || !modelSelect) return;

    const variantSelect = document.querySelector('[data-vehicle-variant-select]');
    const preselectedModelId = modelSelect.dataset.preselected || '';
    const preselectedVariantId = variantSelect ? (variantSelect.dataset.preselected || '') : '';

    brandSelect.addEventListener('change', () => loadModels(brandSelect.value, modelSelect, variantSelect));
    modelSelect.addEventListener('change', () => {
        if (variantSelect) loadVariants(modelSelect.value, variantSelect);
    });

    if (brandSelect.value) {
        loadModels(brandSelect.value, modelSelect, variantSelect, preselectedModelId, preselectedVariantId);
    }
}

function loadModels(brandId, modelSelect, variantSelect, preselectedModelId = '', preselectedVariantId = '') {
    modelSelect.innerHTML = '<option value="">— Chargement... —</option>';
    modelSelect.disabled = true;

    if (!brandId) {
        modelSelect.innerHTML = '<option value="">— Choisissez d\'abord une marque —</option>';
        return;
    }

    fetch(`/vehicules/marques/${brandId}/modeles`)
        .then((r) => r.json())
        .then((data) => {
            modelSelect.innerHTML = '<option value="">— Choisir —</option>';
            data.results.forEach((model) => {
                const opt = document.createElement('option');
                opt.value = model.id;
                opt.textContent = model.name;
                if (String(model.id) === String(preselectedModelId)) {
                    opt.selected = true;
                }
                modelSelect.appendChild(opt);
            });
            modelSelect.disabled = false;

            if (variantSelect && preselectedModelId) {
                loadVariants(preselectedModelId, variantSelect, preselectedVariantId);
            }
        });
}

function loadVariants(modelId, variantSelect, preselectedVariantId = '') {
    variantSelect.innerHTML = '<option value="">— Chargement... —</option>';
    variantSelect.disabled = true;

    if (!modelId) {
        variantSelect.innerHTML = '<option value="">— Choisissez d\'abord un modèle —</option>';
        return;
    }

    fetch(`/vehicules/modeles/${modelId}/variantes`)
        .then((r) => r.json())
        .then((data) => {
            variantSelect.innerHTML = '<option value="">— Choisir —</option>';
            data.results.forEach((variant) => {
                const opt = document.createElement('option');
                opt.value = variant.id;
                opt.textContent = variant.name;
                if (String(variant.id) === String(preselectedVariantId)) {
                    opt.selected = true;
                }
                variantSelect.appendChild(opt);
            });
            variantSelect.disabled = false;
        });
}

/** Bascule Auto (Variante requise) / Moto (Modèle direct, pas de Variante). */
function initVehicleTypeToggle() {
    const radios = document.querySelectorAll('[data-vehicle-type-radio]');
    const variantBlock = document.querySelector('[data-vehicle-variant-block]');
    if (radios.length === 0 || !variantBlock) return;

    const apply = () => {
        const checked = document.querySelector('[data-vehicle-type-radio]:checked');
        const isAuto = checked && checked.value === 'auto';
        variantBlock.style.display = isAuto ? '' : 'none';
        const variantSelect = variantBlock.querySelector('select');
        if (variantSelect) variantSelect.required = isAuto;
    };

    radios.forEach((r) => r.addEventListener('change', apply));
    apply();
}