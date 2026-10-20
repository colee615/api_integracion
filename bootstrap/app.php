<?php

use Illuminate\Http\Request;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        RedirectIfAuthenticated::redirectUsing(function (Request $request): string {
            $user = $request->user();

            if ($user?->isAdmin()) {
                return route('admin.dashboard');
            }

            if ($user?->isCompany()) {
                return route('company.dashboard');
            }

            return url('/');
        });

        Authenticate::redirectUsing(function (): string {
            return route('login');
        });

        $middleware->alias([
            'api.token' => \App\Http\Middleware\AuthenticateApiToken::class,
            'api.ability' => \App\Http\Middleware\RequireApiAbility::class,
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
