document.addEventListener('DOMContentLoaded', () => {
    initNavGroups();
    initDarkModeQuickToggle();
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