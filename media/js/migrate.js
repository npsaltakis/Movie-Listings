/**
 * Mosets Tree -> com_movielist migration driver.
 *
 * @copyright   (C) 2026 Nick Psaltakis. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        const opts = (Joomla.getOptions && Joomla.getOptions('com_movielist.migrate')) || {};
        const startBtn = document.getElementById('ml-migrate-start');

        if (!startBtn || !opts.base) {
            return;
        }

        const LIMIT = 25;
        const bar = document.getElementById('ml-bar');
        const statusEl = document.getElementById('ml-status');
        const progress = document.getElementById('ml-migrate-progress');
        const doneBox = document.getElementById('ml-migrate-done');
        const doneText = document.getElementById('ml-done-text');
        const errBox = document.getElementById('ml-migrate-error');

        let total = 0;
        let offset = 0;
        let images = 0;

        function call(task, extra) {
            const body = new FormData();
            body.append(opts.token, '1');
            if (extra) {
                Object.keys(extra).forEach((k) => body.append(k, extra[k]));
            }

            return fetch(opts.base + '&task=migrate.' + task, {
                method: 'POST',
                body: body,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            }).then((r) => r.json());
        }

        function fail(msg) {
            errBox.textContent = msg || 'Error';
            errBox.classList.remove('d-none');
            startBtn.disabled = false;
        }

        function setBar(pct) {
            bar.style.width = pct + '%';
            bar.textContent = pct + '%';
        }

        function runBatch() {
            call('batch', { offset: offset, limit: LIMIT }).then((res) => {
                if (!res.ok) {
                    return fail(res.error);
                }

                offset += res.done;
                images += res.images || 0;

                const pct = total > 0 ? Math.min(100, Math.round((offset / total) * 100)) : 100;
                setBar(pct);
                statusEl.textContent = offset + ' / ' + total + ' movies · ' + images + ' images copied';

                if (res.done > 0 && offset < total) {
                    runBatch();
                } else {
                    bar.classList.remove('progress-bar-animated', 'progress-bar-striped');
                    doneText.textContent = offset + ' movies migrated, ' + images + ' images copied.';
                    doneBox.classList.remove('d-none');
                }
            }).catch((e) => fail(e.message));
        }

        startBtn.addEventListener('click', function () {
            startBtn.disabled = true;
            errBox.classList.add('d-none');
            doneBox.classList.add('d-none');
            progress.classList.remove('d-none');
            statusEl.textContent = 'Preparing directories, categories and fields…';

            call('prepare').then((res) => {
                if (!res.ok) {
                    return fail(res.error);
                }
                total = res.total;
                offset = 0;
                images = 0;
                statusEl.textContent = 'Migrating movies…';
                runBatch();
            }).catch((e) => fail(e.message));
        });
    });
})();
