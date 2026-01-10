<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
        then: function () {
            // Configurar rate limiters personalizados
            RateLimiter::for('postulacion', function (Request $request) {
                return Limit::perMinute(10)->by($request->ip());
            });

            RateLimiter::for('check-ci', function (Request $request) {
                return Limit::perMinute(20)->by($request->ip());
            });

            RateLimiter::for('public-api', function (Request $request) {
                return Limit::perMinute(60)->by($request->ip());
            });
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();

        // Configuración CORS para API
        $middleware->api(prepend: [
            \Illuminate\Http\Middleware\HandleCors::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
