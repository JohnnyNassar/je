@php
    $current = app()->getLocale();
    $other = $current === 'ar' ? 'en' : 'ar';
    $label = $other === 'ar' ? 'العربية' : 'English';
    $qs = request()->except('lang');
    $qs['lang'] = $other;
@endphp
<a href="{{ url()->current() }}?{{ http_build_query($qs) }}"
   class="text-sm text-gray-600 hover:text-gray-900 underline">
    {{ $label }}
</a>
