<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Alias middleware RBAC.
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureRole::class,
        ]);

        // Security headers (CSP, anti-clickjacking, dst.) untuk seluruh rute web.
        $middleware->web(append: [
            \App\Http\Middleware\SecurityHeaders::class,
        ]);

        // Tamu yang belum login diarahkan ke /login (bukan error 500).
        $middleware->redirectGuestsTo('/login');

        // Jangan pernah percaya header proxy kecuali diatur eksplisit di produksi.
        // (default sudah aman; aktifkan TrustProxies HANYA di belakang reverse proxy tepercaya)
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
