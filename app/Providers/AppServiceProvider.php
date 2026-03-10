<?php

namespace App\Providers;

use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\Facades\Vite;
use Native\Mobile\Network;
use Native\Mobile\System;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // 🛠️ Development Shim for NativePHP Mobile
        // Prevents "Undefined function nativephp_call" when running on Windows/Desktop
        if (! function_exists('nativephp_call')) {
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

        \Livewire\Livewire::component('edit_profile_form', \Joaopaulolndev\FilamentEditProfile\Livewire\EditProfileForm::class);
        \Livewire\Livewire::component('edit_password_form', \Joaopaulolndev\FilamentEditProfile\Livewire\EditPasswordForm::class);
        \Livewire\Livewire::component('delete_account_form', \Joaopaulolndev\FilamentEditProfile\Livewire\DeleteAccountForm::class);
        \Livewire\Livewire::component('browser_sessions_form', \Joaopaulolndev\FilamentEditProfile\Livewire\BrowserSessionsForm::class);
        \Livewire\Livewire::component('username-component', \App\Livewire\UsernameComponent::class);

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
