<x-layouts.shop>
    <div class="max-w-md mx-auto">
        <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
            <div class="px-5 sm:px-7 py-5 border-b border-gray-100">
                <h1 class="text-xl font-semibold text-gray-900">{{ __('Sign in') }}</h1>
                <p class="text-sm text-gray-500 mt-1">{{ __('Welcome back. See your orders or place a new one.') }}</p>
            </div>

            @if (session('status'))
                <div class="mx-5 sm:mx-7 mt-4 rounded-lg bg-green-50 border border-green-200 text-green-800 px-4 py-2 text-sm">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('customer.login') }}" class="px-5 sm:px-7 py-5 space-y-4">
                @csrf

                @if ($errors->any())
                    <div role="alert" class="rounded-lg border border-red-200 bg-red-50 text-red-800 px-4 py-2 text-sm">
                        <ul class="list-disc ps-5 space-y-1">
                            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                        </ul>
                    </div>
                @endif

                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1.5">{{ __('Email') }}</label>
                    <input type="email" name="email" value="{{ old('email') }}" required autofocus
                           class="w-full rounded-md border-gray-300 focus:border-brand-500 focus:ring-brand-500 text-sm">
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1.5">{{ __('Password') }}</label>
                    <input type="password" name="password" required
                           class="w-full rounded-md border-gray-300 focus:border-brand-500 focus:ring-brand-500 text-sm">
                </div>

                <div class="flex items-center justify-between text-xs">
                    <label class="inline-flex items-center gap-2 text-gray-600">
                        <input type="checkbox" name="remember" value="1" class="rounded text-brand-600 focus:ring-brand-500">
                        {{ __('Remember me') }}
                    </label>
                    <a href="{{ route('customer.password.request') }}" class="text-brand-600 hover:text-brand-700">{{ __('Forgot password?') }}</a>
                </div>

                <button type="submit"
                        class="w-full inline-flex items-center justify-center gap-2 bg-brand-600 hover:bg-brand-700 text-white font-semibold px-6 py-3 rounded-md transition shadow-card">
                    {{ __('Sign in') }}
                </button>
            </form>

            <div class="px-5 sm:px-7 py-4 bg-gray-50 border-t border-gray-100 text-center text-sm text-gray-600">
                {{ __('New here?') }}
                <a href="{{ route('customer.register') }}" class="text-brand-600 hover:text-brand-700 font-medium">{{ __('Create an account') }}</a>
            </div>
        </div>
    </div>
</x-layouts.shop>
