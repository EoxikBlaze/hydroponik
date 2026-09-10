<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

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

        // Kecualikan route login dan API dari CSRF agar tidak terjadi error 419 di tunnel
        $middleware->validateCsrfTokens(except: [
            'login',
            'login/*',
            'api/*',
        ]);

        $middleware->alias([
            'auth'    => \Illuminate\Auth\Middleware\Authenticate::class,
            'api.key' => \App\Http\Middleware\ApiKeyMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
