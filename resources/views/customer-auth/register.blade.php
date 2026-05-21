<x-layouts.shop>
    <div class="max-w-md mx-auto">
        <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
            <div class="px-5 sm:px-7 py-5 border-b border-gray-100">
                <h1 class="text-xl font-semibold text-gray-900">{{ __('Create an account') }}</h1>
                <p class="text-sm text-gray-500 mt-1">{{ __('Optional. Saves your address for faster checkout and lets you see all your orders.') }}</p>
            </div>

            <form method="POST" action="{{ route('customer.register') }}" class="px-5 sm:px-7 py-5 space-y-4">
                @csrf

                @if ($errors->any())
                    <div role="alert" class="rounded-lg border border-red-200 bg-red-50 text-red-800 px-4 py-2 text-sm">
                        <ul class="list-disc ps-5 space-y-1">
                            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                        </ul>
                    </div>
                @endif

                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1.5">{{ __('Full Name') }} <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" required autofocus
                           class="w-full rounded-md border-gray-300 focus:border-brand-500 focus:ring-brand-500 text-sm">
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1.5">{{ __('Email') }} <span class="text-red-500">*</span></label>
                    <input type="email" name="email" value="{{ old('email') }}" required dir="ltr"
                           class="w-full rounded-md border-gray-300 focus:border-brand-500 focus:ring-brand-500 text-sm">
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1.5">{{ __('Phone') }} <span class="text-red-500">*</span></label>
                    <input type="tel" name="phone" value="{{ old('phone') }}" required dir="ltr"
                           class="w-full rounded-md border-gray-300 focus:border-brand-500 focus:ring-brand-500 text-sm">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1.5">{{ __('Password') }} <span class="text-red-500">*</span></label>
                        <input type="password" name="password" required
                               class="w-full rounded-md border-gray-300 focus:border-brand-500 focus:ring-brand-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1.5">{{ __('Confirm password') }} <span class="text-red-500">*</span></label>
                        <input type="password" name="password_confirmation" required
                               class="w-full rounded-md border-gray-300 focus:border-brand-500 focus:ring-brand-500 text-sm">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1.5">{{ __('City') }}</label>
                        <input type="text" name="city" value="{{ old('city') }}"
                               class="w-full rounded-md border-gray-300 focus:border-brand-500 focus:ring-brand-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1.5">{{ __('Address') }}</label>
                        <input type="text" name="address" value="{{ old('address') }}"
                               class="w-full rounded-md border-gray-300 focus:border-brand-500 focus:ring-brand-500 text-sm">
                    </div>
                </div>

                <button type="submit"
                        class="w-full inline-flex items-center justify-center gap-2 bg-brand-600 hover:bg-brand-700 text-white font-semibold px-6 py-3 rounded-md transition shadow-card">
                    {{ __('Create account') }}
                </button>
            </form>

            <div class="px-5 sm:px-7 py-4 bg-gray-50 border-t border-gray-100 text-center text-sm text-gray-600">
                {{ __('Already have an account?') }}
                <a href="{{ route('customer.login') }}" class="text-brand-600 hover:text-brand-700 font-medium">{{ __('Sign in') }}</a>
            </div>
        </div>
    </div>
</x-layouts.shop>
