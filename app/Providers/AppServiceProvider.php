<?php

namespace App\Providers;

use App\Database\MySqlProxyConnection;
use App\Filament\Auth\Login;
use App\Filament\Auth\OtpEmailVerificationPrompt;
use App\Filament\Auth\OtpRequestPasswordReset;
use App\Filament\Auth\OtpResetPassword;
use App\Filament\Auth\Register;
use App\Filament\Auth\VerifyOtp;
use App\Livewire\BrowserSessionsComponent;
use App\Livewire\DeleteAccountComponent;
use App\Livewire\EditPasswordComponent;
use App\Livewire\Messages\Inbox;
use App\Livewire\Messages\Messages;
use App\Livewire\Messages\Search;
use App\Livewire\UsernameComponent;
use App\Models\User;
use App\Observers\MediaObserver;
use Filament\Actions\Exports\ExportColumn;
use Filament\Forms\Components\Field;
use Filament\Tables\Filters\BaseFilter;
use Filament\Infolists\Components\Entry;
use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Filament\Tables\Columns\Column;
use Filament\Tables\Table;
use Illuminate\Auth\Events\Login as LoginEvent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Spatie\Backup\BackupServiceProvider;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

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
            require_once __DIR__.'/../../bootstrap/nativephp_shim.php';
        }

        // 🌉 Register MySQL Proxy Driver (For Mobile without pdo_mysql)
        $this->app->resolving('db', function ($db): void {
            $db->extend('mysql_proxy', function ($config, $name) {
                return new MySqlProxyConnection(
                    function () {
                        return new \stdClass;
                    }, // Fake PDO callback
                    $config['database'],
                    $config['prefix'],
                    $config
                );
            });
        });

        if (class_exists('ZipArchive')) {
            if (class_exists(BackupServiceProvider::class)) {
                $this->app->register(BackupServiceProvider::class);
            }
        }

        // ═══════════════════════════════════════════════════════════
        // FIX: filament-mobile-table compatibility with Filament v3/v4
        // Must be registered BEFORE other service providers boot so the
        // macros exist when FilamentMobileTableServiceProvider tries to use them.
        // ═══════════════════════════════════════════════════════════
        $this->app->booting(function (): void {
            // Use object property via array storage per-instance (PHP macros run bound to $this = Table instance)
            Table::macro('extraTableAttributes', function (array $attributes) {
                /** @var mixed $this */
                $key = '__mobileExtraAttrs';
                $existing = data_get((array) $this, $key, []);
                $merged = array_merge($existing, $attributes);
                // Store on the object via the Macroable mechanism
                $this->$key = $merged; // @phpstan-ignore property.notFound

                return $this;
            });
            Table::macro('getExtraTableAttributes', function () {
                /** @var mixed $this */
                $key = '__mobileExtraAttrs';

                return property_exists($this, $key) ? $this->$key : [];
            });
            Table::macro('extraAttributes', function (array $attributes) {
                /** @var mixed $this */
                $key = '__mobileExtraAttrs';
                $existing = data_get((array) $this, $key, []);
                $merged = array_merge($existing, $attributes);
                $this->$key = $merged; // @phpstan-ignore property.notFound

                return $this;
            });
            Table::macro('getExtraAttributes', function () {
                /** @var mixed $this */
                $key = '__mobileExtraAttrs';

                return property_exists($this, $key) ? $this->$key : [];
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
            if (! is_dir($storagePath)) {
                @mkdir($storagePath, 0777, true);
                @mkdir($storagePath.'/framework/views', 0777, true);
                @mkdir($storagePath.'/framework/cache/data', 0777, true);
                @mkdir($storagePath.'/framework/cache/filament', 0777, true);
                @mkdir($storagePath.'/framework/sessions', 0777, true);
                @mkdir($storagePath.'/logs', 0777, true);
                @mkdir($storagePath.'/livewire-tmp', 0777, true);
            }
            config([
                'view.compiled' => $storagePath.'/framework/views',
                'cache.stores.file.path' => $storagePath.'/framework/cache/data',
                'session.files' => $storagePath.'/framework/sessions',
                'filament.cache_path' => $storagePath.'/framework/cache/filament',
                'livewire.temporary_file_upload.directory' => $storagePath.'/livewire-tmp',
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
        Gate::before(function ($user, $ability) {
            return $user->hasRole('super_admin') ? true : null;
        });

        // Automatically activate user on login (Filament/Web/API)
        Event::listen(
            LoginEvent::class,
            function ($event): void {
                $user = $event->user;
                if ($user instanceof User && ! $user->active_status) {
                    $user->update(['active_status' => true]);
                }
            }
        );

        Media::observe(MediaObserver::class);

        Livewire::component('edit_password_form', EditPasswordComponent::class);
        Livewire::component('delete_account_form', DeleteAccountComponent::class);
        Livewire::component('browser_sessions_form', BrowserSessionsComponent::class);
        Livewire::component('fm-inbox', Inbox::class);
        Livewire::component('fm-messages', Messages::class);
        Livewire::component('fm-search', Search::class);
        Livewire::component('username-component', UsernameComponent::class);

        // 🔐 AUTH COMPONENTS (FOR PASSWORD RESET FLOW)
        Livewire::component('app.filament.auth.login', Login::class);
        Livewire::component('app.filament.auth.register', Register::class);
        Livewire::component('app.filament.auth.otp-request-password-reset', OtpRequestPasswordReset::class);
        Livewire::component('app.filament.auth.otp-reset-password', OtpResetPassword::class);
        Livewire::component('app.filament.auth.verify-otp', VerifyOtp::class);
        Livewire::component('app.filament.auth.otp-email-verification-prompt', OtpEmailVerificationPrompt::class);

        // 📱 GLOBAL MOBILE AREA OPTIMIZATION
        // Automatically make ALL Tables into a Card-like layout natively on Mobile
        $isMobile = PHP_OS_FAMILY !== 'Windows' && ! isset($_SERVER['REMOTE_ADDR']) && ! env('DOCKER_ENV');

        Table::configureUsing(function (Table $table) use ($isMobile): void {
            $table->searchable();

            if ($isMobile) {
                // Native Filament Card-like grid for all tables on mobile
                $table->contentGrid([
                    'md' => 1,
                    'lg' => 1,
                ]);
            }
        });

        // 🎯 GLOBAL ALIGNMENT CENTER UNTUK SEMUA TABLE & EXPORTER
        Column::configureUsing(function (Column $column) use ($isMobile): void {
            $column->alignCenter();
            // Otomatis translasi Judul Kolom (Headers) sesuai bahasa terpilih
            // $column->translateLabel();
            
            if ($isMobile) {
                $column->alignCenter();
            }
        });

        // 🎯 GLOBAL AUTO-TRANSLATE UNTUK SEMUA "ISI TABLE" (ROW DATA) PADA WEBDAN NATIVEPHP
        \Filament\Tables\Columns\TextColumn::configureUsing(function (\Filament\Tables\Columns\TextColumn $column): void {
            $column->formatStateUsing(function ($state, $record, \Filament\Tables\Columns\TextColumn $column) {
                // Jangan paksa terjemahan untuk password, token, url, atau email
                if (is_string($state) && !filter_var($state, FILTER_VALIDATE_EMAIL) && !str_contains($state, 'http')) {
                    // Deteksi jika hanya angka dan titik/koma (seperti harga/telepon), dilewati.
                    if (!preg_match('/^[0-9.,\-+() ]+$/', $state)) {
                        return __($state);
                    }
                }
                return $state;
             });
        });

        // 🌐 AUTO TRANSLATE ALL FORM FIELDS & FILTERS
        Field::configureUsing(function (Field $field): void {
            // $field->translateLabel();
        });

        BaseFilter::configureUsing(function (BaseFilter $filter): void {
            // $filter->translateLabel();
        });

        Entry::configureUsing(function (Entry $entry): void {
            // $entry->translateLabel();
        });

        ExportColumn::configureUsing(function (ExportColumn $column): void {
            $column->alignment('center');
            $column->formatStateUsing(function ($state) {
                if (is_string($state) && !filter_var($state, FILTER_VALIDATE_EMAIL) && !str_contains($state, 'http')) {
                    if (!preg_match('/^[0-9.,\-+() ]+$/', $state)) {
                        return __($state);
                    }
                }
                return $state;
            });
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
