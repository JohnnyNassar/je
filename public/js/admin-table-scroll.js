/*
 * Mirrored top horizontal scrollbar for Filament tables (build-free, loaded via
 * AdminPanelProvider renderHook at BODY_END).
 *
 * Filament's table scroll container (.fi-ta-content) only shows its horizontal
 * scrollbar at the BOTTOM, so on wide tables you must scroll all the way down to
 * reach it. This adds a second, synced scrollbar ABOVE the table that scrolls the
 * same content.
 *
 * The bar is injected INSIDE the Livewire-managed DOM, as a sibling of the table
 * content. Livewire's morph compares the live DOM against HTML from the server
 * that knows nothing about the bar, and reconciling that mismatch used to shift
 * the bar's own `display: none` onto .fi-ta-content — the table kept its rows but
 * collapsed to zero height, and only a full refresh brought it back. Clicking
 * page 2 looked like "the products disappeared".
 *
 * So the bar is removed before each Livewire response is applied and rebuilt
 * afterwards: at morph time the DOM matches what the server sent, which is the
 * only arrangement morphing is willing to get right.
 */
(function () {
    var BAR_CLASS = 'fi-ta-top-scroll';

    function attach(content) {
        if (content.dataset.topScroll === '1') return;
        if (!content.querySelector('table')) return;
        content.dataset.topScroll = '1';

        var bar = document.createElement('div');
        bar.className = BAR_CLASS;
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

        // Column widths still change without a Livewire round-trip (toggling a
        // column, an inline edit widening a cell), so keep measuring locally.
        var table = content.querySelector('table');
        var observer = new MutationObserver(sync);
        observer.observe(table, { childList: true, subtree: true, attributes: true });
        content._topScrollObserver = observer;

        sync();
    }

    /**
     * Put the DOM back the way the server rendered it: drop every injected bar,
     * clear the marker so the content can be re-attached, and remove the inline
     * display a previous bad morph may already have stamped onto the content.
     */
    function detach() {
        document.querySelectorAll('.' + BAR_CLASS).forEach(function (bar) {
            bar.remove();
        });

        document.querySelectorAll('.fi-ta-content').forEach(function (content) {
            delete content.dataset.topScroll;

            if (content._topScrollObserver) {
                content._topScrollObserver.disconnect();
                delete content._topScrollObserver;
            }

            // The symptom this whole dance exists to prevent. Harmless when the
            // content was never touched: it only ever gets a display inline
            // style by accident.
            content.style.removeProperty('display');
        });
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

    // Sorting, filtering, searching and paging all arrive as Livewire commits.
    document.addEventListener('livewire:init', function () {
        if (typeof window.Livewire === 'undefined') return;

        window.Livewire.hook('commit', function (commit) {
            // Fires once the response is in but before the DOM is morphed.
            if (typeof commit.respond === 'function') {
                commit.respond(detach);
            }

            // Fires after the morph, on both success and failure, so a failed
            // request cannot leave the table without its scrollbar.
            if (typeof commit.succeed === 'function') {
                commit.succeed(scan);
            }
            if (typeof commit.fail === 'function') {
                commit.fail(scan);
            }
        });
    });
})();
