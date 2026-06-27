/**
 * Minimal self-contained lightbox for com_movielist (poster, director photo, gallery).
 *
 * @copyright   (C) 2026 Nick Psaltakis. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var triggers = Array.prototype.slice.call(document.querySelectorAll('.js-mlb'));

        if (!triggers.length) {
            return;
        }

        var ov = document.createElement('div');
        ov.className = 'mlb-overlay';
        ov.innerHTML =
            '<button type="button" class="mlb-close" aria-label="Close">&times;</button>' +
            '<button type="button" class="mlb-nav mlb-prev" aria-label="Previous">&#10094;</button>' +
            '<figure class="mlb-figure"><img class="mlb-img" alt=""><figcaption class="mlb-caption"></figcaption></figure>' +
            '<button type="button" class="mlb-nav mlb-next" aria-label="Next">&#10095;</button>';
        document.body.appendChild(ov);

        var imgEl = ov.querySelector('.mlb-img');
        var capEl = ov.querySelector('.mlb-caption');
        var navs  = Array.prototype.slice.call(ov.querySelectorAll('.mlb-nav'));
        var group = [];
        var idx   = 0;

        function srcOf(el) {
            return el.getAttribute('data-mlb-src') || el.getAttribute('src');
        }

        function capOf(el) {
            return el.getAttribute('data-mlb-caption') || el.getAttribute('alt') || '';
        }

        function show(i) {
            idx = (i + group.length) % group.length;
            var el = group[idx];
            imgEl.src = srcOf(el);
            var c = capOf(el);
            capEl.textContent = c;
            capEl.style.display = c ? '' : 'none';
            navs.forEach(function (n) { n.style.display = group.length > 1 ? '' : 'none'; });
        }

        function open(el) {
            var g = el.getAttribute('data-mlb-group') || '';
            group = triggers.filter(function (t) { return (t.getAttribute('data-mlb-group') || '') === g; });
            ov.classList.add('is-open');
            document.body.style.overflow = 'hidden';
            show(group.indexOf(el));
        }

        function close() {
            ov.classList.remove('is-open');
            document.body.style.overflow = '';
            imgEl.src = '';
        }

        triggers.forEach(function (t) {
            t.style.cursor = 'zoom-in';
            t.addEventListener('click', function (e) {
                e.preventDefault();
                open(t);
            });
        });

        ov.querySelector('.mlb-close').addEventListener('click', close);
        ov.querySelector('.mlb-prev').addEventListener('click', function (e) { e.stopPropagation(); show(idx - 1); });
        ov.querySelector('.mlb-next').addEventListener('click', function (e) { e.stopPropagation(); show(idx + 1); });
        ov.addEventListener('click', function (e) { if (e.target === ov || e.target.classList.contains('mlb-figure')) { close(); } });

        document.addEventListener('keydown', function (e) {
            if (!ov.classList.contains('is-open')) {
                return;
            }
            if (e.key === 'Escape') {
                close();
            } else if (e.key === 'ArrowLeft') {
                show(idx - 1);
            } else if (e.key === 'ArrowRight') {
                show(idx + 1);
            }
        });
    });
})();
