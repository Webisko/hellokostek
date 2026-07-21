<?php

namespace App\Http\Middleware;

use App\Support\StoreSettings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StoreMaintenanceMode
{
    public function __construct(
        private readonly StoreSettings $storeSettings,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        // 1. If maintenance mode is not enabled, proceed
        if (! $this->storeSettings->maintenanceModeEnabled()) {
            return $next($request);
        }

        // 2. Allow access to the Filament admin panel
        $adminPath = env('FILAMENT_PATH', 'admin');
        if ($request->is($adminPath) || $request->is($adminPath . '/*')) {
            return $next($request);
        }

        // 3. Allow access if the client IP is whitelisted
        $clientIp = $request->ip();
        $allowedIps = $this->storeSettings->maintenanceModeAllowedIps();

        if (filled($clientIp) && in_array($clientIp, $allowedIps, true)) {
            return $next($request);
        }

        // 4. Return 503 Service Unavailable
        $message = $this->storeSettings->maintenanceModeMessage() ?? 'Strona w budowie. Zapraszamy wkrótce!';

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'status' => 'maintenance',
                'message' => $message,
            ], 503);
        }

        return response()->view('errors.503_maintenance', [
            'message' => $message,
        ], 503);
    }
}
