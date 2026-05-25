@php($isAr = app()->getLocale() === 'ar')
{{-- Dismissible cookie notice. Plain JS (no Alpine) so it also works on the
     Coming Soon page, which doesn't load app.js. Choice is kept in localStorage. --}}
<div id="cookie-notice" style="display:none" class="fixed inset-x-0 bottom-0 z-[60] p-3 sm:p-4">
    <div class="max-w-screen-2xl mx-auto bg-gray-900 text-white rounded-xl shadow-2xl px-4 py-3 sm:px-6 sm:py-4 flex flex-col sm:flex-row items-start sm:items-center gap-3">
        <p class="text-sm text-gray-200 flex-1">
            {{ $isAr
                ? 'نستخدم ملفات تعريف الارتباط لقياس الزيارات وتحسين تجربتك.'
                : 'We use cookies to measure visits and improve your experience.' }}
            <a href="{{ route('privacy') }}" class="underline hover:text-white whitespace-nowrap">
                {{ $isAr ? 'سياسة الخصوصية' : 'Privacy Policy' }}
            </a>
        </p>
        <button type="button" id="cookie-accept"
                class="shrink-0 inline-flex items-center justify-center px-4 py-2 rounded-lg bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold">
            {{ $isAr ? 'موافق' : 'Accept' }}
        </button>
    </div>
</div>
<script>
    (function () {
        try {
            if (localStorage.getItem('cookieConsent')) return;
            var el = document.getElementById('cookie-notice');
            var btn = document.getElementById('cookie-accept');
            if (!el) return;
            el.style.display = 'block';
            if (btn) {
                btn.addEventListener('click', function () {
                    try { localStorage.setItem('cookieConsent', '1'); } catch (e) {}
                    el.style.display = 'none';
                });
            }
        } catch (e) {}
    })();
</script>
