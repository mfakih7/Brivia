/**
 * Form enhancements: prevent repeated clicks while submitting (server still
 * enforces idempotency), focus the error summary, and confirm destructive actions.
 */
export function initForms() {
    document.querySelectorAll('form').forEach((form) => {
        form.addEventListener('submit', (event) => {
            const confirmText = form.dataset.confirm;
            if (confirmText && !window.confirm(confirmText)) {
                event.preventDefault();
                return;
            }

            if (form.dataset.submitting === 'true') {
                event.preventDefault();
                return;
            }
            form.dataset.submitting = 'true';

            const submitter = event.submitter;
            if (submitter && submitter.dataset.loadingText !== undefined) {
                submitter.setAttribute('aria-disabled', 'true');
                submitter.classList.add('is-loading');
                const label = submitter.querySelector('[data-label]');
                if (label && submitter.dataset.loadingText) label.textContent = submitter.dataset.loadingText;
            }
        });
    });

    const summary = document.querySelector('[data-error-summary]');
    if (summary) summary.focus();

    document.querySelectorAll('[data-copy]').forEach((button) => {
        button.addEventListener('click', async () => {
            try {
                await navigator.clipboard.writeText(button.dataset.copy);
                button.textContent = 'Copied';
            } catch {
                button.textContent = 'Copy failed';
            }
        });
    });

    // Timezone helper: preselect the visitor's browser timezone when it is an available option.
    document.querySelectorAll('select[data-detect-timezone]').forEach((select) => {
        if (select.dataset.userSelected === 'true') return;
        try {
            const tz = Intl.DateTimeFormat().resolvedOptions().timeZone;
            if (tz && [...select.options].some((o) => o.value === tz)) select.value = tz;
        } catch {
            /* keep server default */
        }
    });
}
