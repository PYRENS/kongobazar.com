document.addEventListener('DOMContentLoaded', () => {
    initCookieConsent();
});

const COOKIE_CONSENT_NAME = 'kb_cookie_consent';
const COOKIE_CONSENT_DAYS = 365;

function initCookieConsent() {
    const overlay = document.getElementById('cookieConsentOverlay');
    if (!overlay) return;

    const existingConsent = getCookieConsent();
    if (!existingConsent) {
        overlay.hidden = false;
    } else {
        applyStoredToggles(existingConsent);
    }

    // Onglets
    document.querySelectorAll('[data-cookie-tab]').forEach((tab) => {
        tab.addEventListener('click', () => switchCookieTab(tab.dataset.cookieTab));
    });
    document.querySelectorAll('[data-cookie-goto-tab]').forEach((btn) => {
        btn.addEventListener('click', () => switchCookieTab(btn.dataset.cookieGotoTab));
    });

    // Interrupteurs (Préférences/Statistiques/Marketing)
    document.querySelectorAll('[data-cookie-toggle]').forEach((toggle) => {
        toggle.addEventListener('click', () => {
            const pressed = toggle.getAttribute('aria-pressed') === 'true';
            toggle.setAttribute('aria-pressed', pressed ? 'false' : 'true');
        });
    });

    // Boutons d'action
    document.querySelectorAll('[data-cookie-action]').forEach((btn) => {
        btn.addEventListener('click', () => handleCookieAction(btn.dataset.cookieAction, overlay));
    });

    // Lien(s) pour rouvrir la bannière (footer : "Politique des cookies")
    document.querySelectorAll('[data-cookie-settings]').forEach((link) => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            switchCookieTab('details');
            overlay.hidden = false;
        });
    });
}

function switchCookieTab(tabName) {
    document.querySelectorAll('.cookie-tab').forEach((t) => t.classList.remove('active'));
    document.querySelectorAll('.cookie-panel').forEach((p) => p.classList.remove('active'));

    const tabBtn = document.querySelector(`[data-cookie-tab="${tabName}"]`);
    const panel = document.getElementById(`cookiePanel-${tabName}`);
    if (tabBtn) tabBtn.classList.add('active');
    if (panel) panel.classList.add('active');
}

function handleCookieAction(action, overlay) {
    let consent;

    if (action === 'reject') {
        consent = { necessary: true, preferences: false, statistics: false, marketing: false };
    } else if (action === 'accept-all') {
        consent = { necessary: true, preferences: true, statistics: true, marketing: true };
    } else if (action === 'accept-selection') {
        consent = {
            necessary: true,
            preferences: getToggleState('preferences'),
            statistics: getToggleState('statistics'),
            marketing: getToggleState('marketing'),
        };
    }

    setCookieConsent(consent);
    overlay.hidden = true;
}

function getToggleState(category) {
    const toggle = document.querySelector(`[data-cookie-toggle="${category}"]`);
    return toggle ? toggle.getAttribute('aria-pressed') === 'true' : false;
}

function applyStoredToggles(consent) {
    ['preferences', 'statistics', 'marketing'].forEach((category) => {
        const toggle = document.querySelector(`[data-cookie-toggle="${category}"]`);
        if (toggle) {
            toggle.setAttribute('aria-pressed', consent[category] ? 'true' : 'false');
        }
    });
}

function setCookieConsent(consent) {
    const value = encodeURIComponent(JSON.stringify({ ...consent, date: new Date().toISOString() }));
    const expires = new Date();
    expires.setDate(expires.getDate() + COOKIE_CONSENT_DAYS);
    document.cookie = `${COOKIE_CONSENT_NAME}=${value}; expires=${expires.toUTCString()}; path=/; SameSite=Lax`;
}

function getCookieConsent() {
    const match = document.cookie.match(new RegExp(`(?:^|; )${COOKIE_CONSENT_NAME}=([^;]*)`));
    if (!match) return null;
    try {
        return JSON.parse(decodeURIComponent(match[1]));
    } catch (e) {
        return null;
    }
}