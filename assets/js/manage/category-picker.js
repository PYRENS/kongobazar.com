document.addEventListener('DOMContentLoaded', () => {
    initCategoryPicker();
});

function initCategoryPicker() {
    const picker = document.querySelector('[data-category-picker]');
    if (!picker) return;

    if (picker.dataset.categoryPickerInitialized) return;
    picker.dataset.categoryPickerInitialized = '1';

    const rootSelect = picker.querySelector('select[data-category-level="1"]');
    const finalInput = document.querySelector('[data-category-final-value]');
    const excludeId = picker.dataset.categoryExclude || '';

    rootSelect.addEventListener('change', () => onCategoryLevelChange(rootSelect, picker, finalInput, 1, excludeId));

    // Pré-sélection (édition) : "12,45,78" = chaîne d'IDs de la racine jusqu'à la catégorie déjà enregistrée.
    const ancestorsAttr = picker.dataset.preselectedAncestors;
    if (ancestorsAttr) {
        const ids = ancestorsAttr.split(',').map((s) => s.trim()).filter(Boolean);
        preselectChain(rootSelect, picker, finalInput, excludeId, ids, 0);
    }
}

function preselectChain(select, picker, finalInput, excludeId, ids, level) {
    if (level >= ids.length) return;

    select.value = ids[level];
    finalInput.value = select.value;

    fetchAndAppendCategoryLevel(select.value, picker, finalInput, level + 2, excludeId, () => {
        const nextSelect = picker.querySelector('select[data-category-level="' + (level + 2) + '"]');
        if (nextSelect) {
            preselectChain(nextSelect, picker, finalInput, excludeId, ids, level + 1);
        }
    });
}

function onCategoryLevelChange(select, picker, finalInput, level, excludeId) {
    // Verrou anti-doublon : si le même changement (même niveau, même valeur) survient
    // deux fois de suite en moins d'une seconde, on ignore le second déclenchement.
    const callKey = level + ':' + select.value;
    const now = Date.now();
    if (onCategoryLevelChange._lastKey === callKey && (now - onCategoryLevelChange._lastTime) < 1000) {
        return;
    }
    onCategoryLevelChange._lastKey = callKey;
    onCategoryLevelChange._lastTime = now;

    picker.querySelectorAll('select[data-category-level]').forEach((s) => {
        if (parseInt(s.dataset.categoryLevel, 10) > level) s.remove();
    });

    finalInput.value = select.value;
    if (!select.value) return;

    fetchAndAppendCategoryLevel(select.value, picker, finalInput, level + 1, excludeId);
}

function fetchAndAppendCategoryLevel(parentId, picker, finalInput, level, excludeId, onDone) {
    const url = excludeId
        ? `/categories/enfants/${parentId}?exclude=${excludeId}`
        : `/categories/enfants/${parentId}`;

    fetch(url)
        .then((r) => r.json())
        .then((data) => {
            if (data.results.length === 0) return;

            const newSelect = document.createElement('select');
            newSelect.dataset.categoryLevel = String(level);
            newSelect.className = picker.querySelector('select[data-category-level="1"]').className;
            newSelect.innerHTML = '<option value="">— Sous-catégorie —</option>';
            data.results.forEach((cat) => {
                const opt = document.createElement('option');
                opt.value = cat.id;
                opt.textContent = cat.name;
                newSelect.appendChild(opt);
            });
            // Toujours attacher l'écouteur, que ce niveau vienne d'une pré-sélection (édition)
            // ou d'un vrai clic de l'utilisateur (création) — sinon plus rien ne réagit après ce niveau.
            newSelect.addEventListener('change', () => onCategoryLevelChange(newSelect, picker, finalInput, level, excludeId));
            picker.appendChild(newSelect);
            if (onDone) onDone();
        });
}