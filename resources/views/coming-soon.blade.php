@php
    $adminPhone = \App\Models\Setting::get('admin_whatsapp');
    $messageEn = \App\Models\Setting::get('coming_soon_message_en', 'Something big is coming.');
    $messageAr = \App\Models\Setting::get('coming_soon_message_ar', 'قريباً جداً.');
    $locale = app()->getLocale();
    $isAr = $locale === 'ar';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $locale) }}" dir="{{ $isAr ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} &mdash; {{ $isAr ? 'قريباً' : 'Coming Soon' }}</title>
    <link rel="icon" href="{{ asset('images/logo.jpg') }}" type="image/jpeg">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css'])
    <style>
        body { background-color: #0f4248; }
        .bg-garage {
            background-image:
                linear-gradient(rgba(15, 66, 72, 0.85), rgba(15, 66, 72, 0.95)),
                repeating-linear-gradient(0deg, rgba(255,255,255,0.04) 0 2px, transparent 2px 8px);
        }
    </style>
</head>
<body class="font-sans antialiased text-white min-h-screen flex flex-col bg-garage">
    <main class="flex-1 flex items-center justify-center px-6 py-12">
        <div class="max-w-2xl w-full text-center">
            <img src="{{ asset('images/logo.jpg') }}"
                 alt="{{ config('app.name') }}"
                 class="mx-auto w-48 h-48 sm:w-64 sm:h-64 rounded-2xl shadow-2xl ring-4 ring-white/10 mb-8 object-cover">

            <div class="inline-flex items-center gap-2 rounded-full bg-red-600/90 px-4 py-1.5 text-xs sm:text-sm font-semibold uppercase tracking-widest mb-6 shadow-lg">
                <span class="w-2 h-2 rounded-full bg-white animate-pulse"></span>
                {{ $isAr ? 'قريباً' : 'Coming Soon' }}
            </div>

            <h1 class="text-3xl sm:text-5xl font-extrabold leading-tight mb-4">
                {{ $isAr ? $messageAr : $messageEn }}
            </h1>

            <p class="text-white/80 text-base sm:text-lg max-w-md mx-auto leading-relaxed mb-10">
                {{ $isAr
                    ? 'نعمل على إطلاق منصة الطلبات الجديدة قريباً جداً. ترقبوا!'
                    : "We're putting the finishing touches on the new ordering platform. Stay tuned." }}
            </p>

            @if ($adminPhone)
                <a href="https://wa.me/{{ $adminPhone }}" target="_blank"
                   class="inline-flex items-center gap-2 bg-[#25D366] hover:bg-[#1DAB52] text-white font-semibold px-6 py-3 rounded-lg shadow-lg transition">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.711.306 1.265.489 1.697.626.713.226 1.362.194 1.875.118.572-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                    {{ $isAr ? 'تواصل معنا على واتساب' : 'Chat with us on WhatsApp' }}
                </a>
            @endif

            <div class="mt-10 flex items-center justify-center gap-3 text-xs text-white/50">
                <a href="?lang={{ $isAr ? 'en' : 'ar' }}" class="hover:text-white underline">
                    {{ $isAr ? 'English' : 'العربية' }}
                </a>
                <span>·</span>
                <a href="/admin/login" class="hover:text-white underline">Admin</a>
            </div>
        </div>
    </main>

    <footer class="text-center text-xs text-white/40 pb-6">
        &copy; {{ date('Y') }} {{ config('app.name') }}
    </footer>
</body>
</html>
