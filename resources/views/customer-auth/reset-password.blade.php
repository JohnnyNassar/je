<x-layouts.shop>
    <div class="max-w-md mx-auto">
        <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
            <div class="px-5 sm:px-7 py-5 border-b border-gray-100">
                <h1 class="text-xl font-semibold text-gray-900">{{ __('Reset password') }}</h1>
            </div>

            <form method="POST" action="{{ route('customer.password.update') }}" class="px-5 sm:px-7 py-5 space-y-4">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                @if ($errors->any())
                    <div role="alert" class="rounded-lg border border-red-200 bg-red-50 text-red-800 px-4 py-2 text-sm">
                        <ul class="list-disc ps-5 space-y-1">
                            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                        </ul>
                    </div>
                @endif

                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1.5">{{ __('Email') }}</label>
                    <input type="email" name="email" value="{{ old('email', $email) }}" required dir="ltr"
                           class="w-full rounded-md border-gray-300 focus:border-brand-500 focus:ring-brand-500 text-sm">
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1.5">{{ __('New password') }}</label>
                    <input type="password" name="password" required
                           class="w-full rounded-md border-gray-300 focus:border-brand-500 focus:ring-brand-500 text-sm">
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1.5">{{ __('Confirm new password') }}</label>
                    <input type="password" name="password_confirmation" required
                           class="w-full rounded-md border-gray-300 focus:border-brand-500 focus:ring-brand-500 text-sm">
                </div>

                <button type="submit"
                        class="w-full inline-flex items-center justify-center gap-2 bg-brand-600 hover:bg-brand-700 text-white font-semibold px-6 py-3 rounded-md transition shadow-card">
                    {{ __('Reset password') }}
                </button>
            </form>
        </div>
    </div>
</x-layouts.shop>
