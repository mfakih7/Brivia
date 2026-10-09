/**
 * Accessible disclosure navigation: aria-expanded toggle, Escape closes and
 * returns focus to the toggle. Without JavaScript the menu is always shown.
 */
export function initNavigation() {
    document.querySelectorAll('[data-nav-toggle]').forEach((toggle) => {
        const panel = document.getElementById(toggle.getAttribute('aria-controls'));
        if (!panel) return;

        const setOpen = (open) => {
            toggle.setAttribute('aria-expanded', String(open));
            panel.dataset.open = String(open);
            const label = toggle.querySelector('[data-nav-toggle-label]');
            if (label) label.textContent = open ? 'Close menu' : 'Open menu';
        };

        setOpen(false);

        toggle.addEventListener('click', () => setOpen(toggle.getAttribute('aria-expanded') !== 'true'));

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') {
                setOpen(false);
                toggle.focus();
            }
        });

        window.matchMedia('(min-width: 1024px)').addEventListener('change', (e) => {
            if (e.matches) setOpen(false);
        });
    });
}
