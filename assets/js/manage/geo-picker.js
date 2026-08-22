document.addEventListener('DOMContentLoaded', () => {
    initGeoPicker();
});

function initGeoPicker() {
    const picker = document.querySelector('[data-geo-picker]');
    if (!picker) return;

    const rootSelect = picker.querySelector('select[data-geo-level="1"]');
    const finalInput = document.querySelector('[data-geo-final-value]');

    rootSelect.addEventListener('change', () => onProvinceChange(rootSelect, picker, finalInput));
}

function onProvinceChange(select, picker, finalInput) {
    // Retire tout ce qui a été construit après le niveau Province
    picker.querySelectorAll('select[data-geo-level]').forEach((s) => {
        if (parseInt(s.dataset.geoLevel, 10) > 1) s.remove();
    });

    finalInput.value = select.value;
    if (!select.value) return;

    const provinceName = select.options[select.selectedIndex].textContent.trim();
    const isKinshasa = provinceName === 'Kinshasa';

    if (isKinshasa) {
        // On saute l'affichage du niveau 2 (Ville), redondant avec la province —
        // mais on doit quand même récupérer l'ID de cette ville en coulisses
        // pour pouvoir demander ses enfants (les communes, niveau 3).
        fetch(`/geo/enfants/${select.value}`)
            .then((r) => r.json())
            .then((data) => {
                if (data.results.length === 0) return;
                const villeId = data.results[0].id; // une seule ville "Kinshasa" en pratique
                finalInput.value = villeId;
                fetchAndAppendLevel(villeId, picker, finalInput, 3); // on affiche directement le niveau 3
            });
        return;
    }

    fetchAndAppendLevel(select.value, picker, finalInput, 2);
}

function fetchAndAppendLevel(parentId, picker, finalInput, level) {
    fetch(`/geo/enfants/${parentId}`)
        .then((r) => r.json())
        .then((data) => {
            if (data.results.length === 0) return;

            const newSelect = document.createElement('select');
            newSelect.dataset.geoLevel = String(level);
            newSelect.className = picker.querySelector('select[data-geo-level="1"]').className;
            newSelect.innerHTML = `<option value="">${data.results[0].typeLabel || '...'}</option>`;
            data.results.forEach((u) => {
                const opt = document.createElement('option');
                opt.value = u.id;
                opt.textContent = u.name;
                newSelect.appendChild(opt);
            });
            newSelect.addEventListener('change', () => onSubLevelChange(newSelect, picker, finalInput, level));
            picker.appendChild(newSelect);
        });
}

function onSubLevelChange(select, picker, finalInput, level) {
    picker.querySelectorAll('select[data-geo-level]').forEach((s) => {
        if (parseInt(s.dataset.geoLevel, 10) > level) s.remove();
    });

    finalInput.value = select.value;
    if (!select.value) return;

    fetchAndAppendLevel(select.value, picker, finalInput, level + 1);
}