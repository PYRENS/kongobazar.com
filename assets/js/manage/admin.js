document.addEventListener('DOMContentLoaded', () => {
    initNavGroups();
    initDarkModeQuickToggle();
    initLiveReorderTables();
});

function initNavGroups() {
    document.querySelectorAll('.admin-nav-group-toggle').forEach((toggle) => {
        toggle.addEventListener('click', () => {
            toggle.closest('.admin-nav-group').classList.toggle('open');
        });
    });
}

function initDarkModeQuickToggle() {
    const btn = document.getElementById('darkModeQuickToggle');
    if (!btn) return;

    btn.addEventListener('click', () => {
        document.body.classList.toggle('admin-dark');
        const isDark = document.body.classList.contains('admin-dark');

        // Sauvegarde en arrière-plan, sans recharger la page ni attendre la réponse
        fetch('/parametres/mode-nuit-rapide', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `dark_mode=${isDark ? 1 : 0}`,
        });
    });
}

/* --------------------------------------------------------------------------
   Tableaux réordonnables en direct (Méga-menu, Top Catégorie)
   Chaque conteneur porte data-live-table ; les formulaires à l'intérieur
   sont interceptés, le serveur renvoie le fragment de table à jour tel quel.
   -------------------------------------------------------------------------- */
function initLiveReorderTables() {
    document.querySelectorAll('[data-live-table]').forEach((container) => {
        container.addEventListener('submit', (e) => {
            const form = e.target.closest('form');
            if (!form || !container.contains(form)) return;
            e.preventDefault();

            fetch(form.action, { method: 'POST' })
                .then((res) => res.text())
                .then((html) => {
                    container.innerHTML = html;
                })
                .catch(() => {
                    // En cas d'échec réseau, on retombe sur le comportement classique
                    form.submit();
                });
        });
    });
}