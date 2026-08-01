document.addEventListener('DOMContentLoaded', () => {
    initGeoCascade();
});

function initGeoCascade() {
    const cascade = document.querySelector('[data-geo-cascade]');
    if (!cascade) return;

    const rootSelect = cascade.querySelector('select[data-geo-level="1"]');
    const extraContainer = cascade.querySelector('[data-geo-extra]');

    const finalInput = document.createElement('input');
    finalInput.type = 'hidden';
    finalInput.name = 'location';
    finalInput.value = rootSelect.value;
    cascade.appendChild(finalInput);

    rootSelect.addEventListener('change', () => onLevelChange(rootSelect, extraContainer, finalInput));
}

function onLevelChange(select, extraContainer, finalInput) {
    const level = parseInt(select.dataset.geoLevel, 10);

    // Retire tous les niveaux créés après celui qui vient de changer
    extraContainer.querySelectorAll('.geo-select-wrapper').forEach((wrapper) => {
        const wrapperLevel = parseInt(wrapper.dataset.level, 10);
        if (wrapperLevel > level) {
            wrapper.remove();
        }
    });

    // Le filtre retient toujours le niveau le plus précis actuellement choisi
    finalInput.value = select.value;

    if (!select.value) return;

    fetch(`/geo/children/${select.value}`)
        .then((response) => response.json())
        .then((data) => {
            if (data.results.length === 0) return; // Pas d'enfants : ce niveau est le plus précis possible

            const wrapper = document.createElement('div');
            wrapper.className = 'geo-select-wrapper';
            wrapper.dataset.level = String(level + 1);

            const newSelect = document.createElement('select');
            newSelect.dataset.geoLevel = String(level + 1);

            const placeholderOption = document.createElement('option');
            placeholderOption.value = '';
            placeholderOption.textContent = data.results[0]?.typeLabel
                ? `Choisir : ${data.results[0].typeLabel}`
                : 'Précisez...';
            newSelect.appendChild(placeholderOption);

            data.results.forEach((unit) => {
                const opt = document.createElement('option');
                opt.value = unit.id;
                opt.textContent = unit.name;
                newSelect.appendChild(opt);
            });

            newSelect.addEventListener('change', () => onLevelChange(newSelect, extraContainer, finalInput));

            wrapper.appendChild(newSelect);
            extraContainer.appendChild(wrapper);
        });
}