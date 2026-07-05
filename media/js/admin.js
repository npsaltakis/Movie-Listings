/**
 * com_movielist admin: show only the custom fields that apply to the selected
 * category's content type (film/script) on the movie edit form.
 */
document.addEventListener('DOMContentLoaded', () => {
    const options = window.Joomla && Joomla.getOptions ? Joomla.getOptions('com_movielist.fieldToggle') : null;

    if (!options || !options.categoryTypes) {
        return;
    }

    const catSelect = document.querySelector('select[name="jform[catid]"]');

    if (!catSelect) {
        return;
    }

    // Collect the toggleable custom fields once; remember the initial
    // "required" state so hiding a field never blocks form validation.
    const entries = [];

    document.querySelectorAll('[class*="movielist-apply-"]').forEach((el) => {
        const match = el.className.match(/movielist-apply-(all|film|script)/);

        if (!match || match[1] === 'all') {
            return;
        }

        entries.push({
            wrap: el.closest('.control-group') || el,
            applyTo: match[1],
        });
    });

    if (!entries.length) {
        return;
    }

    const toggle = () => {
        const type = options.categoryTypes[catSelect.value] || null;

        entries.forEach((entry) => {
            const show = type === null || entry.applyTo === type;

            entry.wrap.style.display = show ? '' : 'none';

            entry.wrap.querySelectorAll('input, select, textarea').forEach((input) => {
                if (!show && input.required) {
                    input.dataset.movielistRequired = '1';
                    input.required = false;
                } else if (show && input.dataset.movielistRequired) {
                    input.required = true;
                    delete input.dataset.movielistRequired;
                }
            });
        });
    };

    catSelect.addEventListener('change', toggle);
    toggle();
});
