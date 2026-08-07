<?php

declare(strict_types=1);

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
    ->withMiddleware(function (Middleware $middleware): void {
        // Cloudflare front-end proxy: trust X-Forwarded-Proto so Laravel
        // generates https URLs (fixes mixed-content "Failed to fetch" when
        // route()/url() returned http:// under the https origin).
        $middleware->trustProxies(
            at: ['*'],
            headers: \Illuminate\Http\Request::HEADER_X_FORWARDED_FOR
                | \Illuminate\Http\Request::HEADER_X_FORWARDED_HOST
                | \Illuminate\Http\Request::HEADER_X_FORWARDED_PORT
                | \Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO
                | \Illuminate\Http\Request::HEADER_X_FORWARDED_AWS_ELB
        );

        $middleware->validateCsrfTokens(except: ['setup/*']);
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureUserHasRole::class,
            'menu' => \App\Http\Middleware\EnsureMenuAccess::class,
            'throttle.requests' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
            'api.token' => \App\Http\Middleware\ApiToken::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
