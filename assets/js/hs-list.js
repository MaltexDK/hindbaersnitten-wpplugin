(function(){
    if (window.__hsnitteListBound) return;
    window.__hsnitteListBound = true;

    function escapeHTML(value) {
        return String(value == null ? '' : value).replace(/[&<>"']/g, function(c){
            return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c];
        });
    }
    document.addEventListener('click', async function(e){
        var sort = e.target.closest && e.target.closest('.hs-sort');
        if (sort && sort.closest('.hs-list-wrap')) {
            var table = sort.closest('table');
            var body = table.querySelector('tbody');
            var th = sort.closest('th');
            var direction = th.getAttribute('aria-sort') === 'ascending' ? 'descending' : 'ascending';
            var key = sort.dataset.key;
            var rows = Array.from(body.querySelectorAll('.hs-row'));
            rows.sort(function(a, b){
                var left = key === 'date' ? a.dataset.dateTimestamp : (a.dataset[key] || '');
                var right = key === 'date' ? b.dataset.dateTimestamp : (b.dataset[key] || '');
                // Missing dates stay last in either direction.
                if (key === 'date' && (left === '' || right === '')) {
                    return left === right ? 0 : left === '' ? 1 : -1;
                }
                var result = key === 'rating' ? Number(left) - Number(right)
                    : key === 'date' ? Number(left) - Number(right)
                    : left.localeCompare(right, 'da', {numeric:true, sensitivity:'base'});
                return direction === 'ascending' ? result : -result;
            });
            rows.forEach(function(row){ body.appendChild(row); });
            table.querySelectorAll('th[aria-sort]').forEach(function(header){
                header.setAttribute('aria-sort', 'none');
                header.querySelector('.caret').textContent = '↕';
            });
            th.setAttribute('aria-sort', direction);
            sort.querySelector('.caret').textContent = direction === 'ascending' ? '▲' : '▼';
            return;
        }
        var a = e.target.closest && e.target.closest('a.hs-open');
        if(!a || !a.closest('.hs-list-wrap')) return;
        e.preventDefault();

        var tr = a.closest('tr.hs-row');
        var id = parseInt(a.getAttribute('data-id')||'0',10);
        var wrap = tr && tr.closest('.hs-list-wrap');
        var base = wrap ? wrap.dataset.detailUrl || '' : '';

        if(!tr) return;

        var detail = document.createElement('div');
        detail.className = 'hs-card hs-detail';
        detail.innerHTML = '<div class="hs-detail-head"><h3></h3></div><div class="hs-detail-body" role="status">Henter…</div>';
        detail.querySelector('h3').textContent = a.textContent.trim();
        if (!window.HSNitteReviewDialog || !window.HSNitteReviewDialog.open(a, detail)) {
            // Fallback for older browsers without native dialog support.
            var old = wrap.querySelector('.hs-list-fallback');
            if (old) old.remove();
            detail.classList.add('hs-list-fallback');
            wrap.appendChild(detail);
        }

        if (!id) {
            detail.querySelector('.hs-detail-body').innerHTML = '<div class="hs-error">Kunne ikke finde ID for denne anmeldelse.</div>';
            return;
        }
        if (!base) {
            detail.querySelector('.hs-detail-body').innerHTML = '<div class="hs-error">Anmeldelsen kunne ikke åbnes. Genindlæs siden og prøv igen.</div>';
            return;
        }

        try {
            var res = await fetch(base + id, {credentials: 'same-origin'});
            var data = await res.json();
            if (!res.ok || typeof data.html !== 'string') throw new Error(data.message || 'Kunne ikke hente anmeldelsen.');
            // Trusted, sanitized HTML from our WordPress renderer, never raw API HTML.
            detail.innerHTML = data.html;
            if (window.HSNitteReviewDialog) window.HSNitteReviewDialog.refresh(detail);
        } catch (err) {
            detail.querySelector('.hs-detail-body').innerHTML = '<div class="hs-error">'+ escapeHTML(err && err.message ? err.message : 'Kunne ikke hente anmeldelsen') +'</div>';
        }
    }, true);
})();
