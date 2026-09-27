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

function addSectionSeeMore(box, term, type) {
    const link = document.createElement('a');
    link.href = `/recherche?q=${encodeURIComponent(term)}&type=${type}`;
    link.className = 'search-suggestion-see-more';
    link.textContent = 'Voir tout';
    box.appendChild(link);
}

function renderSuggestions(data, term, box, form) {
    box.innerHTML = '';
    const {
        products = [], productsHasMore = false,
        categories = [], categoriesHasMore = false,
        sellers = [], sellersHasMore = false,
        relays = [], relaysHasMore = false,
    } = data;

    if (products.length === 0 && categories.length === 0 && sellers.length === 0 && relays.length === 0) {
        box.hidden = true;
        return;
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
        if (productsHasMore) addSectionSeeMore(box, term, 'products');
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
        if (categoriesHasMore) addSectionSeeMore(box, term, 'categories');
    }

    if (sellers.length > 0) {
        const heading = document.createElement('div');
        heading.className = 'suggestion-heading';
        heading.textContent = 'Vendeurs trouvés';
        box.appendChild(heading);

        sellers.forEach((seller) => {
            const link = document.createElement('a');
            link.href = seller.url;
            link.className = 'search-suggestion-item search-suggestion-item--seller';

            const img = seller.logo
                ? `<img src="${seller.logo}" class="suggestion-thumb" alt="">`
                : `<span class="suggestion-thumb suggestion-thumb--empty"></span>`;

            link.innerHTML = `
                ${img}
                <span class="suggestion-title">${seller.name}</span>
                <span class="suggestion-seller-type">${seller.typeLabel}</span>
            `;
            box.appendChild(link);
        });
        if (sellersHasMore) addSectionSeeMore(box, term, 'sellers');
    }

    if (relays.length > 0) {
        const heading = document.createElement('div');
        heading.className = 'suggestion-heading';
        heading.textContent = 'Points relais trouvés';
        box.appendChild(heading);

        relays.forEach((relay) => {
            const link = document.createElement('a');
            link.href = relay.url;
            link.className = 'search-suggestion-item search-suggestion-item--seller';

            const img = relay.logo
                ? `<img src="${relay.logo}" class="suggestion-thumb" alt="">`
                : `<span class="suggestion-thumb suggestion-thumb--empty"></span>`;

            link.innerHTML = `
                ${img}
                <span class="suggestion-title">${relay.name}</span>
                <span class="suggestion-seller-type">${relay.typeLabel}</span>
            `;
            box.appendChild(link);
        });
        if (relaysHasMore) addSectionSeeMore(box, term, 'relays');
    }

    const seeAll = document.createElement('a');
    seeAll.href = `${form.getAttribute('action')}?q=${encodeURIComponent(term)}`;
    seeAll.className = 'search-suggestion-see-all';
    seeAll.textContent = `Voir tous les résultats pour "${term}"`;
    box.appendChild(seeAll);

    box.hidden = false;
}