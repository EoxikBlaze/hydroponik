<?php

use App\Http\Middleware\ApiKeyMiddleware;
use App\Http\Middleware\DemoGuard;
use App\Http\Middleware\PetaniGuard;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Percayai semua reverse proxy (seperti localhost.run, cloudflared, ngrok)
        $middleware->trustProxies(at: '*');

        // Kecualikan route login, API, dan sensor dari CSRF (kompatibel CI4)
        $middleware->validateCsrfTokens(except: [
            'login',
            'login/*',
            'api/*',
            'monitoring/saveSensor',
            'monitoring/saveAlert',
        ]);

        $middleware->alias([
            'auth' => Authenticate::class,
            'api.key' => ApiKeyMiddleware::class,
            'demo' => DemoGuard::class,
            'petani' => PetaniGuard::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
