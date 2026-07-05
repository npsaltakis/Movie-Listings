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

    // Fields whose rendered markup drops the CSS class (e.g. editors behind
    // TinyMCE) are located by their input name instead.
    const tracked = new Set(entries.map((entry) => entry.wrap));

    Object.entries(options.fieldApply || {}).forEach(([name, applyTo]) => {
        const input = document.querySelector('[name="jform[com_fields][' + name + ']"]');
        const wrap = input ? input.closest('.control-group') : null;

        if (wrap && !tracked.has(wrap)) {
            tracked.add(wrap);
            entries.push({wrap, applyTo});
        }
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
                if (!show) {
                    // Joomla's client validation checks the attribute AND the
                    // "required" class; disabling also skips validation and
                    // keeps the value out of the POST.
                    if (input.required) {
                        input.dataset.movielistRequired = '1';
                        input.required = false;
                    }

                    if (input.classList.contains('required')) {
                        input.dataset.movielistReqClass = '1';
                        input.classList.remove('required');
                    }

                    if (!input.disabled) {
                        input.dataset.movielistDisabled = '1';
                        input.disabled = true;
                    }
                } else {
                    if (input.dataset.movielistRequired) {
                        input.required = true;
                        delete input.dataset.movielistRequired;
                    }

                    if (input.dataset.movielistReqClass) {
                        input.classList.add('required');
                        delete input.dataset.movielistReqClass;
                    }

                    if (input.dataset.movielistDisabled) {
                        input.disabled = false;
                        delete input.dataset.movielistDisabled;
                    }
                }
            });
        });
    };

    catSelect.addEventListener('change', toggle);
    toggle();
});
