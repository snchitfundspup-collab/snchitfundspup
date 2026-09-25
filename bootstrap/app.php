<?php

use App\Http\Middleware\RecordUsage;
use App\Http\Middleware\SetBusinessContext;
use App\Http\Middleware\SetLanguage;
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
        /* which business (SN Chit Funds / SN Traders) the page belongs to,
           then log the page for the Usage dashboard */
        $middleware->web(append: [
            SetLanguage::class,
            SetBusinessContext::class,
            RecordUsage::class,
        ]);

        /* the language cookie is written by app.js, so it is not encrypted */
        $middleware->encryptCookies(except: [SetLanguage::COOKIE]);

        /* customers' own pages (/my) have their own sign-in page (/login/customer) */
        $isCustomerPage = fn (Request $request): bool => $request->is('my', 'my/*', 'login/customer', 'login/customer/*');

        $middleware->redirectGuestsTo(
            fn (Request $request) => $isCustomerPage($request) ? route('portal.login') : route('login'),
        );
        $middleware->redirectUsersTo(
            fn (Request $request) => $isCustomerPage($request) ? route('portal.dashboard') : route('dashboard'),
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
