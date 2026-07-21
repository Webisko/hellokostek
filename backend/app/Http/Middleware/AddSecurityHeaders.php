<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AddSecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        if (filter_var(config('app.add_security_headers', true), FILTER_VALIDATE_BOOLEAN) === false) {
            return $next($request);
        }

        /** @var Response $response */
        $response = $next($request);

        // Prevent MIME-type sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Prevent clickjacking — disallow embedding in frames
        $response->headers->set('X-Frame-Options', 'DENY');

        // Control referrer information sent with requests
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Restrict browser features
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=(), payment=()');

        // Content Security Policy (CSP)
        if ($csp = config('app.content_security_policy')) {
            if (app()->environment('local')) {
                $viteHosts = 'http://localhost:5173 http://127.0.0.1:5173';
                $viteWS = 'ws://localhost:5173 ws://127.0.0.1:5173';
                
                $csp = str_replace("script-src 'self'", "script-src 'self' {$viteHosts}", $csp);
                $csp = str_replace("style-src 'self'", "style-src 'self' {$viteHosts}", $csp);
                $csp = str_replace("connect-src 'self'", "connect-src 'self' {$viteHosts} {$viteWS}", $csp);
            }
            $response->headers->set('Content-Security-Policy', $csp);
        }

        // Cross-Origin Opener Policy (COOP)
        if ($coop = config('app.coop_header')) {
            $response->headers->set('Cross-Origin-Opener-Policy', $coop);
        }

        // Cross-Origin Resource Policy (CORP)
        if ($corp = config('app.corp_header')) {
            $response->headers->set('Cross-Origin-Resource-Policy', $corp);
        }

        // Force HTTPS on production (only when the connection is already secure)
        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        return $response;
    }
}
