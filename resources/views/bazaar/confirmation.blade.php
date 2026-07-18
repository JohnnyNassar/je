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
                    <dt class="text-xs text-gray-500">{{ __('Night') }}</dt>
                    <dd class="mt-0.5 font-semibold text-gray-900">{{ $booking->night->label }}</dd>
                </div>
                <div class="rounded-lg bg-gray-50 px-4 py-3">
                    <dt class="text-xs text-gray-500">{{ __('Hours') }}</dt>
                    <dd class="mt-0.5 font-semibold text-gray-900">{{ $booking->night->time_range }}</dd>
                </div>
                <div class="rounded-lg bg-gray-50 px-4 py-3">
                    <dt class="text-xs text-gray-500">{{ __('To pay on the night') }}</dt>
                    <dd class="mt-0.5 font-semibold text-gray-900">{{ money_format($booking->price) }}</dd>
                </div>
                <div class="rounded-lg bg-gray-50 px-4 py-3 sm:col-span-2">
                    <dt class="text-xs text-gray-500">{{ __('Where') }}</dt>
                    <dd class="mt-0.5 font-semibold text-gray-900">{{ __('Amman — 5th Circle') }}</dd>
                </div>
            </dl>

            <div class="mt-8 flex flex-wrap justify-center gap-3">
                <a href="{{ route('bazaar.index') }}"
                   class="inline-flex items-center px-5 py-2.5 rounded-lg text-sm font-semibold bg-brand-600 text-white hover:bg-brand-700">
                    {{ __('Book another night') }}
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
