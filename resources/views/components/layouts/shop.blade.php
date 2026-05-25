@php($cart = app(\App\Services\Cart::class))
@php($cartCount = $cart->itemCount())
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('images/logo.jpg') }}" type="image/jpeg">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <meta name="theme-color" content="#0f4248">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="Joreption">
    <link rel="apple-touch-icon" href="{{ asset('images/logo.jpg') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.analytics')
</head>
<body class="font-sans antialiased bg-gray-50 text-gray-900 min-h-screen flex flex-col">
    <header class="bg-white border-b border-gray-200 sticky top-0 z-40 backdrop-blur supports-[backdrop-filter]:bg-white/95">
        <div class="max-w-screen-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex h-16 items-center justify-between gap-4">
                <a href="{{ route('catalog.index') }}" class="flex items-center gap-2.5 shrink-0">
                    <img src="{{ asset('images/logo.jpg') }}" alt="{{ config('app.name') }}" class="w-9 h-9 rounded-md object-cover ring-1 ring-gray-200">
                    <span class="text-lg font-semibold text-gray-900 hidden sm:inline">{{ __('Joreption') }}</span>
                </a>

                <nav class="hidden md:flex items-center gap-1">
                    <a href="{{ route('catalog.index') }}" class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-100">{{ __('Catalog') }}</a>
                    @guest('customer')
                        <a href="{{ route('track.show') }}" class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-100">{{ __('Track Order') }}</a>
                    @endguest
                    @auth('customer')
                        <a href="{{ route('my-orders.index') }}" class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-100">{{ __('My Orders') }}</a>
                    @endauth
                </nav>

                <div class="flex items-center gap-1">
                    <a href="{{ route('cart.index') }}" aria-label="{{ __('Cart') }}"
                       class="relative inline-flex items-center justify-center w-9 h-9 rounded-full text-gray-700 hover:bg-gray-100 transition">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        @if ($cartCount > 0)
                            <span class="absolute -top-0.5 -end-0.5 inline-flex items-center justify-center px-1.5 h-5 min-w-5 text-[10px] font-bold text-white bg-brand-600 rounded-full ring-2 ring-white">
                                {{ $cartCount }}
                            </span>
                        @endif
                    </a>

                    <div class="ps-2">
                        <x-lang-switcher />
                    </div>

                    @guest('customer')
                        <a href="{{ route('customer.login') }}"
                           class="ms-1 inline-flex items-center px-3 py-1.5 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-100">
                            {{ __('Sign in') }}
                        </a>
                        <a href="{{ route('customer.register') }}"
                           class="hidden sm:inline-flex items-center px-3 py-1.5 rounded-md text-sm font-medium bg-brand-600 text-white hover:bg-brand-700">
                            {{ __('Register') }}
                        </a>
                    @endguest

                    @auth('customer')
                        <div class="relative ms-1" x-data="{ open: false }" @click.outside="open = false">
                            <button type="button" @click="open = !open"
                                    class="inline-flex items-center gap-2 px-2.5 py-1.5 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-100">
                                <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-brand-600 text-white text-xs font-bold">
                                    {{ mb_substr(auth('customer')->user()->name, 0, 1) }}
                                </span>
                                <span class="hidden sm:inline max-w-[7rem] truncate">{{ auth('customer')->user()->name }}</span>
                                <svg class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 20 20" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 8l5 5 5-5"/></svg>
                            </button>
                            <div x-show="open" x-cloak x-transition.opacity
                                 class="absolute end-0 mt-2 w-48 rounded-md bg-white shadow-lg ring-1 ring-black/5 py-1 z-50">
                                <a href="{{ route('my-orders.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                    {{ __('My Orders') }}
                                </a>
                                <a href="{{ route('track.show') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                    {{ __('Track Order') }}
                                </a>
                                <form method="POST" action="{{ route('customer.logout') }}">
                                    @csrf
                                    <button type="submit" class="w-full text-start block px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                        {{ __('Logout') }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endauth

                    @auth
                        <a href="/admin"
                           class="hidden sm:inline-flex items-center px-3 py-1.5 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-100">
                            {{ __('Dashboard') }}
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </header>

    <main class="flex-1 w-full">
        <div class="max-w-screen-2xl mx-auto px-4 sm:px-6 lg:px-8 py-5 sm:py-7">
            @if (session('status'))
                <div role="alert" class="mb-6 flex items-center gap-3 rounded-lg border border-green-200 bg-green-50 text-green-800 px-4 py-3">
                    <svg class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <span class="text-sm font-medium">{{ session('status') }}</span>
                </div>
            @endif

            {{ $slot }}
        </div>
    </main>

    <footer class="bg-white border-t border-gray-200 mt-8">
        <div class="max-w-screen-2xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div>
                    <div class="flex items-center gap-2 mb-3">
                        <img src="{{ asset('images/logo.jpg') }}" alt="{{ config('app.name') }}" class="w-8 h-8 rounded object-cover ring-1 ring-gray-200">
                        <span class="text-base font-semibold text-gray-900">{{ __('Joreption') }}</span>
                    </div>
                    <p class="text-sm text-gray-600 leading-relaxed">
                        {{ __('Cash on Delivery') }}
                    </p>
                </div>
                <div>
                    <h3 class="text-xs font-semibold text-gray-900 mb-3 uppercase tracking-wider">{{ __('Catalog') }}</h3>
                    <ul class="space-y-2 text-sm text-gray-600">
                        <li><a href="{{ route('catalog.index') }}" class="hover:text-brand-600">{{ __('Catalog') }}</a></li>
                        <li><a href="{{ route('cart.index') }}" class="hover:text-brand-600">{{ __('Cart') }}</a></li>
                        <li><a href="{{ route('track.show') }}" class="hover:text-brand-600">{{ __('Track Order') }}</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-xs font-semibold text-gray-900 mb-3 uppercase tracking-wider">{{ __('Language') }}</h3>
                    <div class="text-sm text-gray-600">
                        <x-lang-switcher />
                    </div>
                </div>
            </div>
            <div class="mt-6 pt-5 border-t border-gray-100 text-center text-xs text-gray-500">
                &copy; {{ date('Y') }} {{ __('Joreption') }}
            </div>
        </div>
    </footer>
</body>
</html>
