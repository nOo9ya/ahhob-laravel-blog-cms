<?php

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
        // global middleware
        $middleware->use([
            \App\Http\Middleware\ApiModeCheck::class,
        ]);

        // middleware alias
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminAuth::class,
            'role' => \App\Http\Middleware\RoleAuth::class,
            'track.visitor' => \App\Http\Middleware\TrackVisitor::class,
            'anti.spam' => \App\Http\Middleware\AntiSpam::class,
            'jwt.auth' => \App\Http\Middleware\JwtAuthMiddleware::class,
            'jwt.response' => \App\Http\Middleware\JwtResponseMiddleware::class,
            'jwt.rate' => \App\Http\Middleware\JwtRateLimitMiddleware::class,
        ]);

        // middleware group
        $middleware->group('web', [
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);

        $middleware->group('api', [
            \Illuminate\Routing\Middleware\ThrottleRequests::class.':api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \App\Http\Middleware\JwtResponseMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
