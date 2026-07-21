<?php

require_once __DIR__ . '/../app/Support/IntlPolyfill.php';

use App\Http\Middleware\AddNoindexHeaders;
use App\Http\Middleware\AddSecurityHeaders;
use App\Http\Middleware\StoreMaintenanceMode;
use App\Http\Middleware\SetLocaleMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(StoreMaintenanceMode::class);
        $middleware->append(AddSecurityHeaders::class);
        $middleware->append(AddNoindexHeaders::class);
        $middleware->append(SetLocaleMiddleware::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
