<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $supported = explode(',', env('APP_SUPPORTED_LOCALES', 'en,ar'));

        if ($request->has('lang') && in_array($request->get('lang'), $supported, true)) {
            session()->put('locale', $request->get('lang'));
        }

        $locale = session('locale', config('app.locale'));

        if (in_array($locale, $supported, true)) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
