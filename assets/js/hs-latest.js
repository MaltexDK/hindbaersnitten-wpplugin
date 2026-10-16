(function () {
    'use strict';
    // Native details remains a working inline fallback without JS or dialog support.
    if (window.__hsLatestCardsBound || typeof HTMLDialogElement === 'undefined' || typeof HTMLDialogElement.prototype.showModal !== 'function') return;
    window.__hsLatestCardsBound = true;
    var dialog, content, active, sequence = 0;

    function createDialog() {
        dialog = document.createElement('dialog');
        dialog.className = 'hs-review-dialog';
        dialog.id = 'hs-review-dialog-' + Date.now().toString(36);
        dialog.innerHTML = '<div class="hs-review-dialog-toolbar"><button type="button" class="hs-review-dialog-close" autofocus>Luk <span aria-hidden="true">×</span></button></div><div class="hs-review-dialog-content"></div>';
        content = dialog.querySelector('.hs-review-dialog-content');
        (document.querySelector('.wp-site-blocks') || document.body).appendChild(dialog);
        dialog.querySelector('button').addEventListener('click', function () { dialog.close(); });
        dialog.addEventListener('click', function (event) {
            var rect = dialog.getBoundingClientRect();
            if (event.target === dialog && (event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom)) dialog.close();
        });
        dialog.addEventListener('close', function () {
            document.documentElement.classList.remove('hs-review-modal-open');
            if (!active) return;
            var previous = active;
            active = null;
            previous.item.appendChild(previous.full);
            var target = previous.summary;
            if (target.isConnected && !target.getClientRects().length) {
                target = previous.item.closest('.hs-latest').querySelector('.hs-latest-card');
            }
            if (target && target.isConnected) target.focus({preventScroll: true});
        });
    }

    document.addEventListener('click', function (event) {
        var summary = event.target.closest && event.target.closest('.hs-latest-card');
        if (!summary || !summary.closest('.hs-latest')) return;
        var item = summary.closest('.hs-latest-item');
        var full = item.querySelector('.hs-latest-full');
        if (!full) return;
        if (!dialog) createDialog();
        event.preventDefault();
        if (dialog.open || active) return;
        item.open = false;
        active = {item: item, summary: summary, full: full};
        content.appendChild(full);
        var heading = full.querySelector('.hs-detail-head h3');
        if (heading) {
            if (!heading.id) heading.id = 'hs-review-dialog-title-' + (++sequence);
            dialog.setAttribute('aria-labelledby', heading.id);
            dialog.removeAttribute('aria-label');
        } else {
            dialog.removeAttribute('aria-labelledby');
            dialog.setAttribute('aria-label', 'Anmeldelse');
        }
        summary.setAttribute('aria-haspopup', 'dialog');
        summary.setAttribute('aria-controls', dialog.id);
        document.documentElement.classList.add('hs-review-modal-open');
        dialog.showModal();
        dialog.scrollTop = 0;
        dialog.querySelector('button').focus({preventScroll: true});
    });
})();
