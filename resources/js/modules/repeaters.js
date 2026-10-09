/**
 * Repeatable admin rows. Server always renders a few blank rows so the form
 * works without JavaScript; this adds "Add row" / "Remove" convenience.
 */
export function initRepeaters() {
    document.querySelectorAll('[data-repeater]').forEach((repeater) => {
        const list = repeater.querySelector('[data-repeater-list]');
        const template = repeater.querySelector('template');
        const addButton = repeater.querySelector('[data-repeater-add]');
        const max = Number(repeater.dataset.max || 50);
        if (!list || !template || !addButton) return;

        addButton.hidden = false;

        const reindex = () => {
            [...list.children].forEach((row, index) => {
                row.querySelectorAll('[data-name]').forEach((input) => {
                    input.name = input.dataset.name.replace('__INDEX__', index);
                    const id = `${input.dataset.name.replace(/[^a-z0-9]+/gi, '-')}-${index}`;
                    const label = row.querySelector(`label[data-for="${input.dataset.name}"]`);
                    input.id = id;
                    if (label) label.htmlFor = id;
                });
            });
            addButton.disabled = list.children.length >= max;
        };

        addButton.addEventListener('click', () => {
            if (list.children.length >= max) return;
            list.appendChild(template.content.firstElementChild.cloneNode(true));
            reindex();
            const field = list.lastElementChild.querySelector('input, textarea');
            if (field) field.focus();
        });

        list.addEventListener('click', (event) => {
            const remove = event.target.closest('[data-repeater-remove]');
            if (!remove) return;
            remove.closest('[data-repeater-row]').remove();
            reindex();
            addButton.focus();
        });

        reindex();
    });
}
