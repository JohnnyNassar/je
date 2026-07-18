<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ComingSoonMode
{
    public function handle(Request $request, Closure $next): Response
    {
        $enabled = filter_var(Setting::get('coming_soon_enabled'), FILTER_VALIDATE_BOOLEAN);

        if (! $enabled) {
            return $next($request);
        }

        // The bazaar runs while the shop itself is still behind the splash, so
        // its pages stay publicly reachable regardless of coming-soon mode.
        if ($request->is('admin*') || $request->is('livewire*') || $request->is('privacy')
            || $request->is('bazar*') || $request->is('bazaar*') || auth()->check()) {
            return $next($request);
        }

        return response()->view('coming-soon', [], 200);
    }
}
