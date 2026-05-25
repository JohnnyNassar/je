@php($gaId = trim((string) \App\Models\Setting::get('google_analytics_id')))
@if ($gaId !== '')
    {{-- Google Analytics (GA4) — Measurement ID set in Admin → Settings → Analytics --}}
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $gaId }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', @json($gaId));
    </script>
@endif
