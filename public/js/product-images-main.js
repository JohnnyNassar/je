/**
 * Live "Main" tag for the admin "Product images" field.
 *
 * FilePond reorders items visually using CSS transforms but keeps their DOM
 * order, so a static :first-child rule can't follow a drag. This script tags
 * the visually-top item (smallest on-screen position) with `.is-main-image`
 * and keeps it updated as the list changes (drag, add, remove). It only toggles
 * a CSS class — it never reads or writes the field's saved value, so it cannot
 * affect uploads or ordering data.
 */
(function () {
    'use strict';

    function markMain(field) {
        const items = Array.from(field.querySelectorAll('.filepond--item'));
        if (!items.length) return;
        let best = null;
        let bestKey = Infinity;
        items.forEach(function (it) {
            const r = it.getBoundingClientRect();
            // Top row wins; within a row, left-most wins (grid-safe).
            const key = Math.round(r.top) * 100000 + Math.round(r.left);
            if (key < bestKey) { bestKey = key; best = it; }
        });
        items.forEach(function (it) {
            it.classList.toggle('is-main-image', it === best);
        });
    }

    function attach(field) {
        if (field.__mainTagAttached) return;
        field.__mainTagAttached = true;
        let raf = null;
        const schedule = function () {
            if (raf) return;
            raf = requestAnimationFrame(function () { raf = null; markMain(field); });
        };
        const obs = new MutationObserver(schedule);
        obs.observe(field, { attributes: true, attributeFilter: ['style'], childList: true, subtree: true });
        markMain(field);
    }

    let scanQueued = false;
    function scan() {
        scanQueued = false;
        document.querySelectorAll('.product-images-field').forEach(attach);
    }
    function queueScan() {
        if (scanQueued) return;
        scanQueued = true;
        setTimeout(scan, 150);
    }

    document.addEventListener('DOMContentLoaded', scan);
    // Re-attach after Livewire re-renders the form (e.g. after a save).
    new MutationObserver(queueScan).observe(document.documentElement, { childList: true, subtree: true });
    scan();
})();
