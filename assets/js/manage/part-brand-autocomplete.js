window.initPartBrandAutocomplete = function () {
    const input = document.getElementById('partBrandSearchInput');
    if (!input) return;

    const results = document.getElementById('partBrandSearchResults');
    const hiddenInput = document.getElementById('partBrandIdInput');
    const logoPreview = document.getElementById('partBrandLogoPreview');

    let timeout;

    input.addEventListener('input', function () {
        clearTimeout(timeout);
        hiddenInput.value = '';
        logoPreview.style.display = 'none';
        const term = this.value.trim();
        if (term.length < 2) {
            results.innerHTML = '';
            return;
        }
        timeout = setTimeout(() => {
            fetch('/produits/marques-recherche?q=' + encodeURIComponent(term))
                .then((r) => r.json())
                .then((data) => {
                    results.innerHTML = '';
                    data.results.forEach((brand) => {
                        const item = document.createElement('button');
                        item.type = 'button';
                        item.className = 'list-group-item list-group-item-action';
                        item.textContent = brand.label;
                        item.addEventListener('click', () => {
                            input.value = brand.label;
                            hiddenInput.value = brand.id;
                            results.innerHTML = '';
                            if (brand.logoUrl) {
                                logoPreview.src = brand.logoUrl;
                                logoPreview.style.display = 'block';
                            } else {
                                logoPreview.style.display = 'none';
                            }
                        });
                        results.appendChild(item);
                    });
                });
        }, 300);
    });

    document.addEventListener('click', function (e) {
        if (!input.contains(e.target) && !results.contains(e.target)) {
            results.innerHTML = '';
        }
    });
};