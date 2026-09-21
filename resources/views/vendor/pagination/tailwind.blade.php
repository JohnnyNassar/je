{{--
    Numbered pagination that works on a phone.

    Laravel's stock Tailwind pager renders two pagers and hides one of them by
    breakpoint: below `sm` you get nothing but "« Previous / Next »", with no
    page numbers and no result count. With 200+ products that is 16 pages a
    shopper can only walk one tap at a time, and the two plain buttons at the
    foot of a long grid do not read as pagination at all — the owner reported
    it as "no pagination bar on mobile".

    So there is one pager here for every screen, sized down rather than cut
    down: the count sits above the controls on a phone and beside them from
    `sm` up. Pair it with ->onEachSide(1) so the window stays narrow enough
    for a 360px screen.
--}}
@if ($paginator->hasPages())
    @php
        $linkClasses = 'relative inline-flex items-center justify-center min-w-[2.25rem] h-9 px-2 sm:px-3 text-sm font-medium rounded-md border transition';
        $inactive = 'text-gray-700 bg-white border-gray-300 hover:bg-gray-50';
        $active = 'text-white bg-brand-600 border-brand-600';
        $disabled = 'text-gray-400 bg-gray-50 border-gray-200 cursor-default';
    @endphp

    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}"
         class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

        <p class="text-xs sm:text-sm text-gray-600 text-center sm:text-start">
            {!! __('Showing :first–:last of :total', [
                'first' => '<span class="font-medium">' . $paginator->firstItem() . '</span>',
                'last' => '<span class="font-medium">' . $paginator->lastItem() . '</span>',
                'total' => '<span class="font-medium">' . $paginator->total() . '</span>',
            ]) !!}
        </p>

        {{-- Wraps rather than overflows, so a narrow screen never clips a page number. --}}
        <div class="flex flex-wrap items-center justify-center gap-1 rtl:flex-row-reverse">
            @if ($paginator->onFirstPage())
                <span class="{{ $linkClasses }} {{ $disabled }}" aria-disabled="true">
                    <span aria-hidden="true">&lsaquo;</span>
                    <span class="sr-only">{{ __('Previous') }}</span>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev"
                   class="{{ $linkClasses }} {{ $inactive }}" aria-label="{{ __('Previous') }}">
                    <span aria-hidden="true">&lsaquo;</span>
                </a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="{{ $linkClasses }} {{ $disabled }}" aria-disabled="true">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="{{ $linkClasses }} {{ $active }}" aria-current="page">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="{{ $linkClasses }} {{ $inactive }}"
                               aria-label="{{ __('Go to page :page', ['page' => $page]) }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next"
                   class="{{ $linkClasses }} {{ $inactive }}" aria-label="{{ __('Next') }}">
                    <span aria-hidden="true">&rsaquo;</span>
                </a>
            @else
                <span class="{{ $linkClasses }} {{ $disabled }}" aria-disabled="true">
                    <span aria-hidden="true">&rsaquo;</span>
                    <span class="sr-only">{{ __('Next') }}</span>
                </span>
            @endif
        </div>
    </nav>
@endif
