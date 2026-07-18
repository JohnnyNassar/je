<x-layouts.shop>
    {{-- Hero --}}
    <section class="rounded-2xl bg-brand-900 text-white px-6 py-8 sm:px-10 sm:py-12 relative overflow-hidden">
        <div class="relative">
            <p class="text-accent-400 font-semibold tracking-wide text-sm uppercase">
                {{ __('JorEption Bazar') }}
            </p>
            <h1 class="mt-2 text-3xl sm:text-4xl font-bold leading-tight">
                {{ __('Sell with us — every Thursday & Friday') }}
            </h1>
            <p class="mt-3 text-white/80 max-w-2xl">
                {{ __('An open-air garage sale bazaar in Amman. Book a table, bring your things, and sell them to the crowd.') }}
            </p>

            <dl class="mt-7 grid grid-cols-2 sm:grid-cols-4 gap-4 sm:gap-6 text-sm">
                <div>
                    <dt class="text-white/60">{{ __('Where') }}</dt>
                    <dd class="mt-1 font-semibold">{{ __('Amman — 5th Circle') }}</dd>
                </div>
                <div>
                    <dt class="text-white/60">{{ __('Thursdays') }}</dt>
                    <dd class="mt-1 font-semibold">{{ __('6:00 PM – midnight') }}</dd>
                </div>
                <div>
                    <dt class="text-white/60">{{ __('Fridays') }}</dt>
                    <dd class="mt-1 font-semibold">{{ __('4:00 PM – midnight') }}</dd>
                </div>
                <div>
                    <dt class="text-white/60">{{ __('Per table, per night') }}</dt>
                    <dd class="mt-1 font-semibold">{{ money_format($price) }}</dd>
                </div>
            </dl>
        </div>
    </section>

    @if ($errors->any())
        <div role="alert" class="mt-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            {{ $errors->first() }}
        </div>
    @endif

    @if ($nights->isEmpty())
        <div class="mt-8 rounded-xl border border-gray-200 bg-white p-8 text-center">
            <p class="text-gray-600">{{ __('The season has finished. Thank you to everyone who joined us!') }}</p>
        </div>
    @else
        {{-- Booking: pick a night, pick a table, leave your details --}}
        <section class="mt-8"
                 x-data="{
                     selected: null,
                     selectedNumber: null,
                     select(id, number) {
                         this.selected = id;
                         this.selectedNumber = number;
                         this.$nextTick(() => this.$refs.form?.scrollIntoView({ behavior: 'smooth', block: 'start' }));
                     },
                 }">

            <div class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-7">
                <div class="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900">{{ __('Book your table') }}</h2>
                        <p class="mt-1 text-sm text-gray-600">
                            {{ __('Choose a night, then tap a free table on the plan.') }}
                        </p>
                    </div>

                    {{-- Night picker: plain GET so availability is always server-truth --}}
                    <form method="GET" action="{{ route('bazaar.index') }}" class="min-w-[15rem]">
                        <label for="night" class="block text-xs font-medium text-gray-500 mb-1">
                            {{ __('Night') }}
                        </label>
                        <select name="night" id="night" onchange="this.form.submit()"
                                class="w-full rounded-lg border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500">
                            @foreach ($nights as $night)
                                <option value="{{ $night->id }}" @selected($selectedNight && $night->id === $selectedNight->id)>
                                    {{ $night->label }} — {{ $night->availableCount() }} {{ __('free') }}
                                </option>
                            @endforeach
                        </select>
                        <noscript>
                            <button type="submit" class="mt-2 text-sm underline">{{ __('Show') }}</button>
                        </noscript>
                    </form>
                </div>

                @if ($selectedNight)
                    <div class="mt-5 flex flex-wrap items-center gap-3 text-sm">
                        <span class="inline-flex items-center rounded-full bg-brand-50 text-brand-800 px-3 py-1 font-medium">
                            {{ $selectedNight->label }} · {{ $selectedNight->time_range }}
                        </span>
                        @if ($selectedNight->isSoldOut())
                            <span class="inline-flex items-center rounded-full bg-red-50 text-red-700 px-3 py-1 font-medium">
                                {{ __('Fully booked') }}
                            </span>
                        @else
                            <span class="text-gray-600">
                                {{ __(':n tables still free', ['n' => $selectedNight->availableCount()]) }}
                            </span>
                        @endif
                    </div>

                    <div class="mt-5">
                        @include('bazaar.partials.floorplan')
                    </div>

                    {{-- Details form, revealed once a table is picked --}}
                    <div x-ref="form" x-show="selected" x-cloak class="mt-8 border-t border-gray-200 pt-6">
                        <form method="POST" action="{{ route('bazaar.book') }}" class="space-y-4">
                            @csrf
                            <input type="hidden" name="bazaar_night_id" value="{{ $selectedNight->id }}">
                            <input type="hidden" name="bazaar_table_id" x-bind:value="selected">

                            <div class="flex items-center justify-between gap-4">
                                <h3 class="text-lg font-semibold text-gray-900">
                                    {{ __('Table') }} <span x-text="'#' + selectedNumber"></span>
                                </h3>
                                <button type="button" x-on:click="selected = null; selectedNumber = null"
                                        class="text-sm text-gray-500 underline">
                                    {{ __('Change table') }}
                                </button>
                            </div>

                            <div class="grid sm:grid-cols-2 gap-4">
                                <div>
                                    <label for="vendor_name" class="block text-sm font-medium text-gray-700 mb-1">
                                        {{ __('Your name') }} <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" name="vendor_name" id="vendor_name" required
                                           value="{{ old('vendor_name') }}"
                                           class="w-full rounded-lg border-gray-300 focus:border-brand-500 focus:ring-brand-500">
                                </div>

                                <div>
                                    <label for="vendor_phone" class="block text-sm font-medium text-gray-700 mb-1">
                                        {{ __('Phone') }} <span class="text-red-500">*</span>
                                    </label>
                                    <input type="tel" name="vendor_phone" id="vendor_phone" required
                                           value="{{ old('vendor_phone') }}" placeholder="07 9999 9999"
                                           class="w-full rounded-lg border-gray-300 focus:border-brand-500 focus:ring-brand-500">
                                </div>

                                <div>
                                    <label for="vendor_business" class="block text-sm font-medium text-gray-700 mb-1">
                                        {{ __('Shop or brand name') }}
                                        <span class="text-gray-400 font-normal">({{ __('optional') }})</span>
                                    </label>
                                    <input type="text" name="vendor_business" id="vendor_business"
                                           value="{{ old('vendor_business') }}"
                                           class="w-full rounded-lg border-gray-300 focus:border-brand-500 focus:ring-brand-500">
                                </div>

                                <div>
                                    <label for="goods_description" class="block text-sm font-medium text-gray-700 mb-1">
                                        {{ __('What will you sell?') }}
                                        <span class="text-gray-400 font-normal">({{ __('optional') }})</span>
                                    </label>
                                    <input type="text" name="goods_description" id="goods_description"
                                           value="{{ old('goods_description') }}"
                                           class="w-full rounded-lg border-gray-300 focus:border-brand-500 focus:ring-brand-500">
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center justify-between gap-4 pt-2">
                                <p class="text-sm text-gray-600">
                                    {{ __('Total') }}:
                                    <span class="font-semibold text-gray-900">{{ money_format($price) }}</span>
                                    <span class="text-gray-500">— {{ __('paid in cash on the night') }}</span>
                                </p>
                                <button type="submit"
                                        class="inline-flex items-center px-5 py-2.5 rounded-lg text-sm font-semibold bg-brand-600 text-white hover:bg-brand-700">
                                    {{ __('Request this table') }}
                                </button>
                            </div>

                            <p class="text-xs text-gray-500">
                                {{ __('We will call you on WhatsApp to confirm. Your table is held once we confirm.') }}
                            </p>
                        </form>
                    </div>
                @endif
            </div>
        </section>

        {{-- Full season schedule --}}
        <section class="mt-10">
            <h2 class="text-xl font-semibold text-gray-900">{{ __('All bazaar nights') }}</h2>
            <p class="mt-1 text-sm text-gray-600">
                {{ __('Every Thursday and Friday until 30 October 2026.') }}
            </p>

            <ul class="mt-4 grid sm:grid-cols-2 lg:grid-cols-3 gap-2.5">
                @foreach ($nights as $night)
                    <li>
                        <a href="{{ route('bazaar.index', ['night' => $night->id]) }}"
                           class="flex items-center justify-between gap-3 rounded-lg border px-4 py-3 text-sm transition
                                  {{ $selectedNight && $night->id === $selectedNight->id
                                     ? 'border-brand-500 bg-brand-50'
                                     : 'border-gray-200 bg-white hover:border-gray-300' }}">
                            <span class="font-medium text-gray-900">{{ $night->label }}</span>
                            @if ($night->isSoldOut())
                                <span class="text-xs font-medium text-red-600">{{ __('Full') }}</span>
                            @else
                                <span class="text-xs text-gray-500">
                                    {{ __(':n free', ['n' => $night->availableCount()]) }}
                                </span>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
</x-layouts.shop>
