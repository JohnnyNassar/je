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

        if ($request->is('admin*') || $request->is('livewire*') || $request->is('privacy') || auth()->check()) {
            return $next($request);
        }

        return response()->view('coming-soon', [], 200);
    }
}
