window.initAdCategoryAutocomplete = function () {
    const input = document.getElementById('adCategorySearchInput');
    if (!input) return;

    const results = document.getElementById('adCategorySearchResults');
    const hiddenInput = document.getElementById('adCategoryIdInput');
    const clearBtn = document.getElementById('adCategoryClearBtn');
    const toggleBtn = document.getElementById('adCategoryToggleBtn');

    let timeout;

    function renderResults(items) {
        results.innerHTML = '';
        if (items.length === 0) {
            results.style.display = 'none';
            return;
        }
        items.forEach((cat) => {
            const item = document.createElement('button');
            item.type = 'button';
            item.className = 'list-group-item list-group-item-action';
            item.textContent = cat.name;
            item.addEventListener('click', () => {
                input.value = cat.name;
                hiddenInput.value = cat.id;
                clearBtn.style.display = 'block';
                results.style.display = 'none';
            });
            results.appendChild(item);
        });
        results.style.display = 'block';
    }

    function search(term) {
        fetch('/categories/recherche-json?q=' + encodeURIComponent(term))
            .then((r) => r.json())
            .then((data) => renderResults(data.results));
    }

    input.addEventListener('input', function () {
        clearTimeout(timeout);
        hiddenInput.value = '';
        clearBtn.style.display = 'none';
        const term = this.value.trim();
        if (term.length < 2) {
            results.style.display = 'none';
            return;
        }
        timeout = setTimeout(() => search(term), 300);
    });

    // Flèche : parcourt toute la liste (recherche vide) sans avoir à taper
    toggleBtn.addEventListener('click', () => {
        if (results.style.display === 'block') {
            results.style.display = 'none';
            return;
        }
        search('');
    });

    // Croix : réinitialise complètement le champ
    clearBtn.addEventListener('click', () => {
        input.value = '';
        hiddenInput.value = '';
        clearBtn.style.display = 'none';
        results.style.display = 'none';
        input.focus();
    });

    document.addEventListener('click', function (e) {
        if (!input.closest('.position-relative').contains(e.target)) {
            results.style.display = 'none';
        }
    });
};

document.addEventListener('DOMContentLoaded', () => {
    if (typeof window.initAdCategoryAutocomplete === 'function') {
        window.initAdCategoryAutocomplete();
    }
});