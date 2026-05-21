/**
 * Alpine component for the media picker modal.
 * Registered globally so the modal Blade doesn't need any inline JS.
 */
document.addEventListener('alpine:init', () => {
    window.Alpine.data('mediaPicker', (statePath) => ({
        q: '',
        picked: null,
        statePath: statePath,

        async pickImage(el, path) {
            this.picked = path;
            console.log('[media-picker] click', { statePath: this.statePath, path });
            console.log('[media-picker] $wire present?', !!this.$wire);

            if (this.$wire && typeof this.$wire.call === 'function') {
                try {
                    await this.$wire.call('pickMediaToState', this.statePath, path);
                    console.log('[media-picker] $wire.call OK');
                    return;
                } catch (e) {
                    console.error('[media-picker] $wire.call failed', e);
                }
            }

            if (this.$wire && typeof this.$wire.set === 'function') {
                try {
                    await this.$wire.set(this.statePath, path);
                    console.log('[media-picker] $wire.set OK');
                } catch (e) {
                    console.error('[media-picker] $wire.set failed', e);
                }
            }

            // Try to close the surrounding Filament modal
            const root = el.closest('.fi-modal-window')
                || el.closest('[role="dialog"]')
                || el.closest('.fi-modal');
            if (root) {
                const btn = root.querySelector('button[aria-label="Close"]')
                    || root.querySelector('.fi-modal-close-btn')
                    || root.querySelector('button.fi-modal-close-btn');
                if (btn) { btn.click(); return; }
            }

            try { window.dispatchEvent(new CustomEvent('close-modal')); } catch (e) {}
        },
    }));
});
