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
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'api.token' => \App\Http\Middleware\AuthenticateApiToken::class,
            'internal.api' => \App\Http\Middleware\AuthenticateInternalApi::class,
            'company.portal' => \App\Http\Middleware\AuthenticateCompanyPortal::class,
            'company.locale' => \App\Http\Middleware\SetCompanyLocale::class,
            'admin' => \App\Http\Middleware\EnsureAdmin::class,
            'company' => \App\Http\Middleware\EnsureCompany::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
