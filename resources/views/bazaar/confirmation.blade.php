<x-layouts.shop>
    <div class="max-w-2xl mx-auto">
        <div class="rounded-2xl border border-gray-200 bg-white p-6 sm:p-9 text-center">
            <div class="mx-auto w-14 h-14 rounded-full bg-green-50 flex items-center justify-center">
                <svg class="w-7 h-7 text-green-600" fill="none" viewBox="0 0 24 24"
                     stroke-width="2" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                </svg>
            </div>

            <h1 class="mt-5 text-2xl font-bold text-gray-900">{{ __('Table requested') }}</h1>
            <p class="mt-2 text-gray-600">
                {{ __('We have your request. We will contact you on WhatsApp to confirm the table.') }}
            </p>

            <dl class="mt-7 grid sm:grid-cols-2 gap-x-6 gap-y-4 text-start">
                <div class="rounded-lg bg-gray-50 px-4 py-3">
                    <dt class="text-xs text-gray-500">{{ __('Table') }}</dt>
                    <dd class="mt-0.5 font-semibold text-gray-900">
                        #{{ $booking->table->number }} · {{ __($booking->table->section_label) }}
                    </dd>
                </div>
                <div class="rounded-lg bg-gray-50 px-4 py-3">
                    <dt class="text-xs text-gray-500">{{ __('Weekend') }}</dt>
                    <dd class="mt-0.5 font-semibold text-gray-900">{{ $booking->period->label }}</dd>
                </div>
                <div class="rounded-lg bg-gray-50 px-4 py-3 sm:col-span-2">
                    <dt class="text-xs text-gray-500">{{ __('Nights included') }}</dt>
                    <dd class="mt-0.5 font-semibold text-gray-900">
                        @foreach ($booking->period->nights as $night)
                            {{ $night->label }} · {{ $night->time_range }}@if (! $loop->last)<br>@endif
                        @endforeach
                    </dd>
                </div>
                @if ($booking->category)
                    <div class="rounded-lg bg-gray-50 px-4 py-3">
                        <dt class="text-xs text-gray-500">{{ __('What you sell') }}</dt>
                        <dd class="mt-0.5 font-semibold text-gray-900">{{ $booking->category->name }}</dd>
                    </div>
                @endif
                <div class="rounded-lg bg-gray-50 px-4 py-3">
                    <dt class="text-xs text-gray-500">{{ __('Where') }}</dt>
                    <dd class="mt-0.5 font-semibold text-gray-900">{{ __('Amman — 5th Circle') }}</dd>
                </div>
            </dl>

            <div class="mt-5 rounded-lg border border-gray-200 p-4 text-start">
                <dl class="space-y-1.5 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-600">{{ __('Table for the weekend') }}</dt>
                        <dd class="font-medium text-gray-900">{{ money_format($booking->price) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-600">
                            {{ __('Refundable deposit') }}
                            <span class="text-gray-400">— {{ __('returned if there is no damage') }}</span>
                        </dt>
                        <dd class="font-medium text-gray-900">{{ money_format($booking->deposit) }}</dd>
                    </div>
                    <div class="flex justify-between border-t border-gray-200 pt-1.5 mt-1.5">
                        <dt class="font-semibold text-gray-900">{{ __('Due on the night') }}</dt>
                        <dd class="font-semibold text-gray-900">{{ money_format($booking->total_due) }}</dd>
                    </div>
                </dl>
            </div>

            @if ($booking->documents->isNotEmpty())
                <div class="mt-4 rounded-lg bg-gray-50 px-4 py-3 text-start">
                    <p class="text-xs text-gray-500">
                        {{ __('Received:') }}
                        @foreach ($booking->documentSummary() as $label => $count)
                            {{ __($label) }}@if ($count > 1) &times;{{ $count }}@endif{{ ! $loop->last ? ' · ' : '' }}
                        @endforeach
                    </p>
                    <ul class="mt-1.5 space-y-0.5">
                        @foreach ($booking->documents as $document)
                            <li class="text-sm text-gray-700 truncate">
                                {{ $document->original_name }}
                                <span class="text-xs text-gray-400">({{ $document->size_for_humans }})</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($booking->isMissingHealthCertificate())
                <div role="alert" class="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 text-start">
                    {{ __('We still need your health certificate before you can trade. Please send it to us on WhatsApp.') }}
                </div>
            @endif

            <div class="mt-8 flex flex-wrap justify-center gap-3">
                <a href="{{ route('bazaar.index') }}"
                   class="inline-flex items-center px-5 py-2.5 rounded-lg text-sm font-semibold bg-brand-600 text-white hover:bg-brand-700">
                    {{ __('Book another weekend') }}
                </a>
                <a href="{{ route('catalog.index') }}"
                   class="inline-flex items-center px-5 py-2.5 rounded-lg text-sm font-semibold border border-gray-300 text-gray-700 hover:bg-gray-50">
                    {{ __('Visit the shop') }}
                </a>
            </div>
        </div>

        <p class="mt-4 text-center text-xs text-gray-500">
            {{ __('Reference') }}: #{{ $booking->id }}
        </p>
    </div>
</x-layouts.shop>
