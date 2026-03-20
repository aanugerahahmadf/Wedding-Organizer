<?php

use App\Http\Middleware\SetLocale;
use App\Http\Middleware\VerifyCsrfToken;
use App\Providers\AutoTranslationServiceProvider;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;

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
        AutoTranslationServiceProvider::class,
    ])
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Mendaftarkan middleware SetLocale ke group web agar session dan auth tersedia
        $middleware->web(append: [
            SetLocale::class,
        ]);

        // Define mobile group for NativePHP
        $middleware->group('mobile', [
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Session\Middleware\StartSession::class,
            SetLocale::class
        ]);

        $middleware->replace(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class, VerifyCsrfToken::class);

        // Cuma trust proxies kalau di Vercel/Production
        if (env('VERCEL') || env('APP_ENV') === 'production') {
            $middleware->trustProxies('*');
        }
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

return $app;
