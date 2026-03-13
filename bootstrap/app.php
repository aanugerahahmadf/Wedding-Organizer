<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| Vercel Storage Redirection
|--------------------------------------------------------------------------
| On Vercel, the filesystem is read-only. We need to redirect storage, 
| cache, and views to /tmp during the build and at runtime.
*/
// Logic moved to AppServiceProvider to avoid premature config() calls.


$app = Application::configure(basePath: dirname(__DIR__))
    ->withProviders([
        App\Providers\AutoTranslationServiceProvider::class,
    ])
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
        ]);

        $middleware->api(append: [
            \App\Http\Middleware\SetLocale::class,
        ]);

        $middleware->web(replace: [
            ValidateCsrfToken::class => VerifyCsrfToken::class,
        ]);

        // Cuma trust proxies kalau di Vercel/Production
        if (env('VERCEL') || env('APP_ENV') === 'production') {
            $middleware->trustProxies(at: '*');
        }
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

return $app;
