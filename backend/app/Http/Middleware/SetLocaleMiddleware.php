<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocaleMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->header('Accept-Language');

        if (filled($locale)) {
            $locale = strtolower(substr(explode(',', $locale)[0], 0, 2));
            
            if (in_array($locale, ['pl', 'en'], true)) {
                App::setLocale($locale);
            }
        }

        return $next($request);
    }
}
