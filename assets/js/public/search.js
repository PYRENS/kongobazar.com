document.addEventListener('DOMContentLoaded', () => {
    initSearchAutocomplete();
});

function initSearchAutocomplete() {
    document.querySelectorAll('.search-form, .search-form-condensed, .search-form-condensed-desktop').forEach((form) => {
        const input = form.querySelector('input[name="q"]');
        const box = form.querySelector('.search-suggestions');
        if (!input || !box) return;

        let debounceTimer = null;

        input.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            const term = input.value.trim();

            if (term.length < 2) {
                box.hidden = true;
                box.innerHTML = '';
                return;
            }

            debounceTimer = setTimeout(() => fetchSuggestions(term, box, form), 300);
        });

        document.addEventListener('click', (e) => {
            if (!form.contains(e.target)) {
                box.hidden = true;
            }
        });
    });
}

function fetchSuggestions(term, box, form) {
    fetch(`/recherche/suggest?q=${encodeURIComponent(term)}`)
        .then((response) => response.json())
        .then((data) => renderSuggestions(data, term, box, form))
        .catch(() => {
            box.hidden = true;
        });
}

function renderSuggestions(data, term, box, form) {
    box.innerHTML = '';
    const { products = [], categories = [] } = data;

    if (products.length === 0 && categories.length === 0) {
        box.hidden = true;
        return;
    }

    if (categories.length > 0) {
        const heading = document.createElement('div');
        heading.className = 'suggestion-heading';
        heading.textContent = 'Catégories trouvées';
        box.appendChild(heading);

        categories.forEach((cat) => {
            const link = document.createElement('a');
            link.href = cat.url;
            link.className = 'search-suggestion-item search-suggestion-item--category';
            link.innerHTML = `<i class="bi ${cat.icon} suggestion-icon"></i><span class="suggestion-title">${cat.name}</span>`;
            box.appendChild(link);
        });
    }

    if (products.length > 0) {
        const heading = document.createElement('div');
        heading.className = 'suggestion-heading';
        heading.textContent = 'Produits trouvés';
        box.appendChild(heading);

        products.forEach((item) => {
            const link = document.createElement('a');
            link.href = item.url;
            link.className = 'search-suggestion-item';

            const img = item.image
                ? `<img src="${item.image}" class="suggestion-thumb" alt="">`
                : `<span class="suggestion-thumb suggestion-thumb--empty"></span>`;

            link.innerHTML = `
                ${img}
                <span class="suggestion-title">${item.title}</span>
                <span class="suggestion-price">${item.price} ${item.currency}</span>
            `;
            box.appendChild(link);
        });
    }

    const seeAll = document.createElement('a');
    seeAll.href = `${form.getAttribute('action')}?q=${encodeURIComponent(term)}`;
    seeAll.className = 'search-suggestion-see-all';
    seeAll.textContent = `Voir tous les résultats pour "${term}"`;
    box.appendChild(seeAll);

    box.hidden = false;
}