<?php

namespace App\Providers;

use BezhanSalleh\FilamentLanguageSwitch\LanguageSwitch;
use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Native\Mobile\Network;
use Native\Mobile\System;
use TomatoPHP\FilamentLanguageSwitcher\FilamentLanguageSwitcherPlugin;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // 🛠️ Development Shim for NativePHP Mobile
        // Prevents "Undefined function nativephp_call" when running on Windows/Desktop
        if (! function_exists('App\Providers\nativephp_call')) {
            function nativephp_call($method, $params) {
                \Illuminate\Support\Facades\Log::info("Native call (Shim): {$method}", json_decode($params, true));
                return json_encode(['status' => 'success']);
            }
        }

        // 🌉 Register MySQL Proxy Driver (For Mobile without pdo_mysql)
        $this->app->resolving('db', function ($db): void {
            $db->extend('mysql_proxy', function ($config, $name) {
                return new \App\Database\MySqlProxyConnection(
                    function() { return new \stdClass(); }, // Fake PDO callback
                    $config['database'],
                    $config['prefix'],
                    $config
                );
            });
        });

        if (class_exists('ZipArchive')) {
            if (class_exists(\Spatie\Backup\BackupServiceProvider::class)) {
                $this->app->register(\Spatie\Backup\BackupServiceProvider::class);
            }
        }

        // ═══════════════════════════════════════════════════════════
        // FIX: filament-mobile-table compatibility with Filament v3/v4
        // Must be registered BEFORE other service providers boot so the
        // macros exist when FilamentMobileTableServiceProvider tries to use them.
        // ═══════════════════════════════════════════════════════════
        $this->app->booting(function (): void {
            // Use object property via array storage per-instance (PHP macros run bound to $this = Table instance)
            \Filament\Tables\Table::macro('extraTableAttributes', function (array $attributes) {
                /** @var \Filament\Tables\Table $this */
                $key = '__mobileExtraAttrs';
                $existing = data_get((array) $this, $key, []);
                $merged = array_merge($existing, $attributes);
                // Store on the object via the Macroable mechanism
                $this->$key = $merged;  // @phpstan-ignore-line

                return $this;
            });
            \Filament\Tables\Table::macro('getExtraTableAttributes', function () {
                /** @var \Filament\Tables\Table $this */
                $key = '__mobileExtraAttrs';

                return property_exists($this, $key) ? $this->$key : [];  // @phpstan-ignore-line
            });
            \Filament\Tables\Table::macro('extraAttributes', function (array $attributes) {
                /** @var \Filament\Tables\Table $this */
                $key = '__mobileExtraAttrs';
                $existing = data_get((array) $this, $key, []);
                $merged = array_merge($existing, $attributes);
                $this->$key = $merged;  // @phpstan-ignore-line

                return $this;
            });
            \Filament\Tables\Table::macro('getExtraAttributes', function () {
                /** @var \Filament\Tables\Table $this */
                $key = '__mobileExtraAttrs';

                return property_exists($this, $key) ? $this->$key : [];  // @phpstan-ignore-line
            });
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // 🚀 Vercel Read-Only Filesystem Fix
        if (env('VERCEL')) {
            $storagePath = '/tmp/storage';
            if (!is_dir($storagePath)) {
                @mkdir($storagePath, 0777, true);
                @mkdir($storagePath . '/framework/views', 0777, true);
                @mkdir($storagePath . '/framework/cache/data', 0777, true);
                @mkdir($storagePath . '/framework/sessions', 0777, true);
                @mkdir($storagePath . '/logs', 0777, true);
            }
            config([
                'view.compiled' => $storagePath . '/framework/views',
                'cache.stores.file.path' => $storagePath . '/framework/cache/data',
                'session.files' => $storagePath . '/framework/sessions',
            ]);
        }

        // ═══════════════════════════════════════════════════════════
        // PERSISTENT SESSION CONFIGURATION (WEB & MOBILE)
        // ═══════════════════════════════════════════════════════════
        // Pastikan session tidak pernah expired selama server hidup
        config([
            'session.expire_on_close' => false,
            'session.lottery' => [0, 100], // Matikan Garbage Collection (0% chance)
        ]);

        // Grant all permissions to super_admin role
        \Illuminate\Support\Facades\Gate::before(function ($user, $ability) {
            return $user->hasRole('super_admin') ? true : null;
        });

        // Automatically activate user on login (Filament/Web/API)
        \Illuminate\Support\Facades\Event::listen(
            \Illuminate\Auth\Events\Login::class,
            function ($event): void {
                $user = $event->user;
                if ($user instanceof \App\Models\User && ! $user->active_status) {
                    $user->update(['active_status' => true]);
                }
            }
        );

        \Spatie\MediaLibrary\MediaCollections\Models\Media::observe(\App\Observers\MediaObserver::class);

        \Livewire\Livewire::component('edit_password_form', \App\Livewire\EditPasswordComponent::class);
        \Livewire\Livewire::component('delete_account_form', \App\Livewire\DeleteAccountComponent::class);
        \Livewire\Livewire::component('browser_sessions_form', \App\Livewire\BrowserSessionsComponent::class);
        \Livewire\Livewire::component('fm-inbox', \App\Livewire\Messages\Inbox::class);
        \Livewire\Livewire::component('fm-messages', \App\Livewire\Messages\Messages::class);
        \Livewire\Livewire::component('fm-search', \App\Livewire\Messages\Search::class);
        \Livewire\Livewire::component('username-component', \App\Livewire\UsernameComponent::class);

        // 🔐 AUTH COMPONENTS (FOR PASSWORD RESET FLOW)
        \Livewire\Livewire::component('app.filament.auth.login', \App\Filament\Auth\Login::class);
        \Livewire\Livewire::component('app.filament.auth.register', \App\Filament\Auth\Register::class);
        \Livewire\Livewire::component('app.filament.auth.otp-request-password-reset', \App\Filament\Auth\OtpRequestPasswordReset::class);
        \Livewire\Livewire::component('app.filament.auth.otp-reset-password', \App\Filament\Auth\OtpResetPassword::class);
        \Livewire\Livewire::component('app.filament.auth.verify-otp', \App\Filament\Auth\VerifyOtp::class);
        \Livewire\Livewire::component('app.filament.auth.otp-email-verification-prompt', \App\Filament\Auth\OtpEmailVerificationPrompt::class);

        // 📱 GLOBAL MOBILE AREA OPTIMIZATION
        // Automatically make ALL Tables into a Card-like layout natively on Mobile
        $isMobile = PHP_OS_FAMILY !== 'Windows' && !isset($_SERVER['REMOTE_ADDR']) && !env('DOCKER_ENV');

        \Filament\Tables\Table::configureUsing(function (\Filament\Tables\Table $table) use ($isMobile): void {
            if ($isMobile) {
                // Native Filament Card-like grid for all tables on mobile
                $table->contentGrid([
                    'md' => 1,
                    'lg' => 1,
                ]);
            }
        });

        // Use built-in column stacking for descriptions instead of forcing attributes
        \Filament\Tables\Columns\Column::configureUsing(function (\Filament\Tables\Columns\Column $column) use ($isMobile): void {
            // Already handled by native Filament behavior when contentGrid is used
        });

        FilamentAsset::register([
            Css::make('app-stylesheet', Vite::asset('resources/css/app.css')),
            // Fallback: register mobile-cards CSS directly in case the vendor package's
            // service provider failed to register it due to the macro compatibility issue.
            // Css::make('mobile-cards-styles', base_path('vendor/slym758/filament-mobile-table/resources/css/mobile-cards.css')),
        ]);

        // Singletons are now registered in NativeServiceProvider
    }
}
