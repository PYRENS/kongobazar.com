// Rend "en direct" n'importe quel formulaire de filtre marqué data-live-filter, sans recharger
// la page : chaque changement (case cochée, prix ajusté) relance le filtre aussitôt, et les
// options de filtre elles-mêmes se mettent à jour pour suivre les produits déjà filtrés.
// Pour l'utiliser sur une nouvelle page : ajouter ces attributs aux bons endroits, et faire en
// sorte que le contrôleur réponde en JSON ({ html, filtersHtml, counts, url }) quand la requête
// est en Ajax (voir SearchController::search() pour l'exemple de référence).
document.addEventListener('DOMContentLoaded', () => {
    // Bouton "Autour de moi" dans le filtre : trouve le lieu connu le plus proche de la
    // position réelle du visiteur, puis déclenche le filtrage comme n'importe quel autre choix.
    document.querySelectorAll('#filterGeoBtn').forEach((btn) => {
        btn.addEventListener('click', () => {
            if (!navigator.geolocation) return;
            const form = btn.closest('form');
            if (!form) return;

            const originalHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="bi bi-geo-alt"></i> Localisation en cours...';

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const { latitude, longitude } = position.coords;
                    fetch(`/geo/plus-proche?lat=${latitude}&lon=${longitude}`)
                        .then((r) => r.json())
                        .then((data) => {
                            btn.disabled = false;
                            btn.innerHTML = originalHtml;
                            if (!data.found) return;

                            let locationInput = form.querySelector('input[name="location"]');
                            if (!locationInput) {
                                locationInput = document.createElement('input');
                                locationInput.type = 'hidden';
                                locationInput.name = 'location';
                                form.appendChild(locationInput);
                            }
                            locationInput.value = data.id;

                            form.dispatchEvent(new Event('change', { bubbles: true }));
                        });
                },
                () => {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-geo-alt"></i> Réessayer';
                }
            );
        });
    });

    document.querySelectorAll('[data-live-filter]').forEach((form) => {
        const targetId = form.dataset.liveFilterTarget;
        const target = document.getElementById(targetId);
        const selfTargetId = form.dataset.liveFilterSelfTarget;
        if (!target) return;

        // Les cases à cocher et la barre de prix sont régénérées à chaque mise à jour du
        // filtre lui-même : on doit donc réattacher leurs petits comportements (Voir tout,
        // synchronisation des 2 curseurs) après chaque remplacement, pas une seule fois au départ.
        function wireFilterWidgets(container) {
            container.querySelectorAll('[data-see-all]').forEach((btn) => {
                if (btn.dataset.wired === 'true') return; // déjà câblé, ne pas ajouter un 2ème écouteur
                btn.dataset.wired = 'true';

                const countLabel = btn.textContent.match(/\((\d+)\)/)?.[0] ?? '';
                btn.addEventListener('click', () => {
                    const extras = btn.parentElement.querySelectorAll('.filter-checkbox-extra');
                    const isExpanded = btn.dataset.expanded === 'true';

                    extras.forEach((el) => { el.hidden = isExpanded; });
                    btn.dataset.expanded = isExpanded ? 'false' : 'true';
                    btn.innerHTML = isExpanded
                        ? `<i class="bi bi-chevron-down"></i> Voir tout ${countLabel}`
                        : `<i class="bi bi-chevron-up"></i> Voir moins`;
                });
            });

            container.querySelectorAll('[data-price-range]').forEach((wrap) => {
                const minInput = wrap.querySelector('[data-price-range-min]');
                const maxInput = wrap.querySelector('[data-price-range-max]');
                const minLabel = wrap.querySelector('[data-price-range-min-label]');
                const maxLabel = wrap.querySelector('[data-price-range-max-label]');
                const minHidden = wrap.querySelector('[data-price-range-min-hidden]');
                const maxHidden = wrap.querySelector('[data-price-range-max-hidden]');
                const fill = wrap.querySelector('[data-price-range-fill]');
                const rangeMin = parseFloat(wrap.dataset.min);
                const rangeMax = parseFloat(wrap.dataset.max);

                function update() {
                    let minVal = parseFloat(minInput.value);
                    let maxVal = parseFloat(maxInput.value);
                    if (minVal > maxVal) { [minVal, maxVal] = [maxVal, minVal]; }
                    minInput.value = minVal;
                    maxInput.value = maxVal;
                    minLabel.textContent = minVal;
                    maxLabel.textContent = maxVal;
                    minHidden.value = minVal;
                    maxHidden.value = maxVal;
                    const left = ((minVal - rangeMin) / (rangeMax - rangeMin)) * 100;
                    const right = ((maxVal - rangeMin) / (rangeMax - rangeMin)) * 100;
                    fill.style.left = left + '%';
                    fill.style.width = (right - left) + '%';
                }

                minInput.addEventListener('input', update);
                maxInput.addEventListener('input', update);
                update();
            });
        }

function hasActiveFilters() {
            // Les cases cochées comptent toujours. Le prix ne compte que s'il a vraiment été
            // resserré (ses champs cachés portent toujours une valeur, même par défaut, sinon).
            // Le lieu ne compte que si une valeur a été choisie dans la cascade.
            if (form.querySelector('input[type="checkbox"]:checked')) return true;

            const priceWrap = form.querySelector('[data-price-range]');
            if (priceWrap) {
                const minHidden = priceWrap.querySelector('[data-price-range-min-hidden]');
                const maxHidden = priceWrap.querySelector('[data-price-range-max-hidden]');
                if (minHidden && parseFloat(minHidden.value) > parseFloat(priceWrap.dataset.min)) return true;
                if (maxHidden && parseFloat(maxHidden.value) < parseFloat(priceWrap.dataset.max)) return true;
            }

            const locationInput = form.querySelector('input[name="location"]');
            if (locationInput && locationInput.value) return true;

            return false;
        }

        function toggleResetVisibility() {
            const active = hasActiveFilters();

            const topBtn = document.querySelector('.search-filter-reset-icon');
            if (topBtn) topBtn.hidden = !active;

            document.querySelectorAll('.search-tabs-reset-btn').forEach((el) => {
                el.hidden = !active;
            });

            document.querySelectorAll('.search-filter-reset-link').forEach((el) => {
                el.classList.toggle('is-disabled', !active);
            });
        }

        function applyResponse(data) {
            target.innerHTML = data.html;

            if (data.filtersHtml && selfTargetId) {
                const selfTarget = document.getElementById(selfTargetId);
                if (selfTarget) {
                    selfTarget.innerHTML = data.filtersHtml;
                    wireFilterWidgets(selfTarget);
                }
            }

            if (data.counts) {
                Object.entries(data.counts).forEach(([key, value]) => {
                    document.querySelectorAll(`[data-live-filter-count="${key}"]`).forEach((el) => {
                        el.textContent = value;
                    });
                });
            }
            if (data.url) {
                window.history.pushState({}, '', data.url);
            }
        }

        function fetchAndApply(url) {
            target.style.opacity = '0.5';
            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then((r) => r.json())
                .then((data) => applyResponse(data))
                .finally(() => { target.style.opacity = '1'; });
        }

        function submitLive() {
            const params = new URLSearchParams(new FormData(form));
            fetchAndApply(`${form.action}?${params.toString()}`);
            toggleResetVisibility();
        }

        form.addEventListener('submit', (e) => {
            e.preventDefault();
            submitLive();
        });

        form.addEventListener('change', (e) => {
            if (e.target.matches('input[type="checkbox"], input[type="range"], select')) {
                submitLive();
            }
        });

        target.addEventListener('click', (e) => {
            const loadMoreLink = e.target.closest('[data-live-filter-loadmore]');
            if (loadMoreLink) {
                e.preventDefault();
                fetchAndApply(loadMoreLink.href);
            }
        });

        document.querySelectorAll('[data-live-filter-reset]').forEach((resetLink) => {
            resetLink.addEventListener('click', (e) => {
                e.preventDefault();
                if (resetLink.classList.contains('is-disabled')) return;
                fetchAndApply(resetLink.href);
                form.reset();
            });
        });

        wireFilterWidgets(form);
        toggleResetVisibility();
    });
});