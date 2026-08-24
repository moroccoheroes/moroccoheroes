<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        $locale = $request->session()->get('locale', config('app.locale'));

        if (array_key_exists($locale, config('locales.supported'))) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}