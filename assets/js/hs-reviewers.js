(function () {
    'use strict';
    if (window.__hsReviewersBound) return;
    window.__hsReviewersBound = true;

    async function load(reviewer, button) {
        if (reviewer.dataset.loading === 'true') return;
        var box = reviewer.querySelector('.hs-reviewer-reviews');
        reviewer.dataset.loading = 'true';
        box.setAttribute('aria-busy', 'true');
        if (button) button.disabled = true;
        else box.innerHTML = '<p role="status">Henter anmeldelser…</p>';
        var status = document.createElement('p');
        status.className = 'hs-reviewer-status';
        status.setAttribute('role', 'status');
        box.appendChild(status);
        try {
            var response = await fetch(button ? button.dataset.url : reviewer.dataset.url, {credentials:'same-origin'});
            var data = await response.json();
            if (!response.ok || typeof data.html !== 'string') throw new Error('Kunne ikke hente anmeldelserne.');
            // Both tables and links are escaped by the same WordPress list renderer.
            var fragment = document.createElement('div');
            fragment.innerHTML = data.html;
            if (button) {
                var table = box.querySelector('.hs-table');
                var rows = fragment.querySelectorAll('.hs-row');
                rows.forEach(function (row) { table.querySelector('tbody').appendChild(row); });
                // Reset sort indicators: new rows arrive in date order.
                table.querySelectorAll('th[aria-sort]').forEach(function (th) {
                    th.setAttribute('aria-sort', 'none');
                    th.querySelector('.caret').textContent = '↕';
                });
                var next = fragment.querySelector('.hs-reviews-more');
                if (next) button.replaceWith(next);
                else button.remove();
                // Focus the first newly loaded review when a pagination button disappears.
                if (rows.length) rows[0].querySelector('.hs-open').focus({preventScroll:true});
                status.textContent = rows.length + ' flere anmeldelser indlæst.';
            } else {
                box.replaceChildren.apply(box, Array.from(fragment.childNodes));
            }
            reviewer.dataset.loaded = 'true';
        } catch (error) {
            status.textContent = 'Kunne ikke hente anmeldelserne. Prøv igen.';
            if (!button) {
                box.replaceChildren(status);
                var retry = document.createElement('button');
                retry.type = 'button';
                retry.className = 'hs-reviewer-retry';
                retry.textContent = 'Prøv igen';
                box.appendChild(retry);
            }
        } finally {
            delete reviewer.dataset.loading;
            box.removeAttribute('aria-busy');
            if (button) button.disabled = false;
        }
    }
    document.addEventListener('toggle', function (event) {
        var reviewer = event.target;
        if (!reviewer.matches || !reviewer.matches('.hs-reviewer') || !reviewer.open || reviewer.dataset.loaded === 'true') return;
        load(reviewer);
    }, true);
    document.addEventListener('click', function (event) {
        var button = event.target.closest && event.target.closest('.hs-reviews-more, .hs-reviewer-retry');
        var reviewer = button && button.closest('.hs-reviewer');
        if (!reviewer) return;
        reviewer.querySelectorAll('.hs-reviewer-status').forEach(function (status) { status.remove(); });
        load(reviewer, button.matches('.hs-reviews-more') ? button : null);
    });
})();
