/*
 * Mirrored top horizontal scrollbar for Filament tables (build-free, loaded via
 * AdminPanelProvider renderHook at BODY_END).
 *
 * Filament's table scroll container (.fi-ta-content) only shows its horizontal
 * scrollbar at the BOTTOM, so on wide tables you must scroll all the way down to
 * reach it. This adds a second, synced scrollbar ABOVE the table that scrolls the
 * same content. Re-syncs on resize and on Livewire re-renders (sort/filter/page).
 */
(function () {
    function attach(content) {
        if (content.dataset.topScroll === '1') return;
        if (!content.querySelector('table')) return;
        content.dataset.topScroll = '1';

        var bar = document.createElement('div');
        bar.className = 'fi-ta-top-scroll';
        var inner = document.createElement('div');
        inner.className = 'fi-ta-top-scroll-inner';
        bar.appendChild(inner);
        content.parentNode.insertBefore(bar, content);

        function sync() {
            inner.style.width = content.scrollWidth + 'px';
            bar.style.display = content.scrollWidth > content.clientWidth + 1 ? 'block' : 'none';
        }

        var lock = false;
        bar.addEventListener('scroll', function () {
            if (lock) return; lock = true; content.scrollLeft = bar.scrollLeft; lock = false;
        });
        content.addEventListener('scroll', function () {
            if (lock) return; lock = true; bar.scrollLeft = content.scrollLeft; lock = false;
        });
        window.addEventListener('resize', sync);

        // The table's rows/columns change on sort, filter and pagination (Livewire
        // morphs the <table> in place) — re-measure the spacer when they do.
        new MutationObserver(sync).observe(content.querySelector('table'), {
            childList: true, subtree: true, attributes: true,
        });

        sync();
    }

    var pending = false;
    function scan() {
        if (pending) return;
        pending = true;
        requestAnimationFrame(function () {
            pending = false;
            document.querySelectorAll('.fi-ta-content').forEach(attach);
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        scan();
        new MutationObserver(scan).observe(document.body, { childList: true, subtree: true });
    });
    document.addEventListener('livewire:navigated', scan);
})();
