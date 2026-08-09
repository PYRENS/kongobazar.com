document.addEventListener('DOMContentLoaded', () => {
    initCategoryPicker();
});

function initCategoryPicker() {
    const picker = document.querySelector('[data-category-picker]');
    if (!picker) return;

    const rootSelect = picker.querySelector('select[data-category-level="1"]');
    const finalInput = document.querySelector('[data-category-final-value]');
    const excludeId = picker.dataset.categoryExclude || '';

    rootSelect.addEventListener('change', () => onCategoryLevelChange(rootSelect, picker, finalInput, 1, excludeId));
}

function onCategoryLevelChange(select, picker, finalInput, level, excludeId) {
    picker.querySelectorAll('select[data-category-level]').forEach((s) => {
        if (parseInt(s.dataset.categoryLevel, 10) > level) s.remove();
    });

    finalInput.value = select.value;
    if (!select.value) return;

    fetchAndAppendCategoryLevel(select.value, picker, finalInput, level + 1, excludeId);
}

function fetchAndAppendCategoryLevel(parentId, picker, finalInput, level, excludeId) {
    const url = excludeId
        ? `/categories/enfants/${parentId}?exclude=${excludeId}`
        : `/categories/enfants/${parentId}`;

    fetch(url)
        .then((r) => r.json())
        .then((data) => {
            if (data.results.length === 0) return;

            const newSelect = document.createElement('select');
            newSelect.dataset.categoryLevel = String(level);
            newSelect.className = 'form-select mb-2';
            newSelect.innerHTML = '<option value="">— Sous-catégorie —</option>';
            data.results.forEach((cat) => {
                const opt = document.createElement('option');
                opt.value = cat.id;
                opt.textContent = cat.name;
                newSelect.appendChild(opt);
            });
            newSelect.addEventListener('change', () => onCategoryLevelChange(newSelect, picker, finalInput, level, excludeId));
            picker.appendChild(newSelect);
        });
}