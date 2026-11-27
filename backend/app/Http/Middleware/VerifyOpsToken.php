<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyOpsToken
{
    public function handle(Request $request, Closure $next, string $scope = 'health'): Response
    {
        $expectedToken = match ($scope) {
            'ingest' => (string) config('services.ops.ingest_token', ''),
            default => (string) config('services.ops.health_token', ''),
        };

        // Fail-closed: if token is not configured on the server, deny access completely.
        if ($expectedToken === '') {
            return response()->json([
                'message' => 'Telemetria lub odbiór zdarzeń Ops nie są skonfigurowane na serwerze.',
            ], 503);
        }

        $bearerToken = (string) $request->bearerToken();
        $timestampRaw = (string) $request->header('X-Webisko-Timestamp', '');
        $signature = (string) $request->header('X-Webisko-Signature', '');

        if ($bearerToken === '' || $timestampRaw === '' || $signature === '' || ! ctype_digit($timestampRaw)) {
            return response()->json([
                'message' => 'Brak wymaganych nagłówków autoryzacyjnych Webisko Ops.',
            ], 401);
        }

        if (! hash_equals($expectedToken, $bearerToken)) {
            return response()->json([
                'message' => 'Nieprawidłowy token dostępu Webisko Ops.',
            ], 401);
        }

        $timestamp = (int) $timestampRaw;
        if (abs(time() - $timestamp) > 300) {
            return response()->json([
                'message' => 'Znacznik czasu żądania Webisko Ops wygasł.',
            ], 401);
        }

        $path = '/' . ltrim($request->path(), '/');
        $payload = $timestamp . '.' . strtoupper($request->method()) . '.' . $path . '.' . $request->getContent();
        $expectedSignature = hash_hmac('sha256', $payload, $expectedToken);

        if (! hash_equals($expectedSignature, strtolower($signature))) {
            return response()->json([
                'message' => 'Nieprawidłowy podpis HMAC żądania Webisko Ops.',
            ], 401);
        }

        return $next($request);
    }
}
