// Décompte des cartes "Vente flash" : une seule minuterie pour toutes les cartes de la page,
// y compris celles ajoutées plus tard par le défilement infini.
(function () {
    // Décalage entre l'horloge du visiteur et celle du serveur (l'heure du serveur fait foi)
    const script = document.currentScript;
    const serverNow = script ? Date.parse(script.dataset.serverNow) : NaN;
    const skew = isNaN(serverNow) ? 0 : serverNow - Date.now();

    function pad(n) {
        return String(n).padStart(2, '0');
    }

    function setUnit(el, selector, value) {
        const target = el.querySelector(selector);
        if (target) target.textContent = pad(value);
    }

    function tick() {
        const now = Date.now() + skew;

        document.querySelectorAll('[data-flash-end]').forEach((el) => {
            const end = Date.parse(el.dataset.flashEnd);
            if (isNaN(end)) return;

            let remaining = Math.floor((end - now) / 1000);

            if (remaining <= 0) {
                ['[data-cd-days]', '[data-cd-hours]', '[data-cd-mins]', '[data-cd-secs]'].forEach((s) => setUnit(el, s, 0));
                el.classList.add('is-ended');
                el.classList.remove('is-urgent');
                return;
            }

            el.classList.toggle('is-urgent', remaining < 3600);

            const days = Math.floor(remaining / 86400);
            remaining %= 86400;
            const hours = Math.floor(remaining / 3600);
            remaining %= 3600;
            const minutes = Math.floor(remaining / 60);
            const seconds = remaining % 60;

            setUnit(el, '[data-cd-days]', days);
            setUnit(el, '[data-cd-hours]', hours);
            setUnit(el, '[data-cd-mins]', minutes);
            setUnit(el, '[data-cd-secs]', seconds);
        });
    }

    tick();
    setInterval(tick, 1000);
})();