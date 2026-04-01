<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Native\Mobile\Network;
use Native\Mobile\Providers\SystemServiceProvider;
use Native\Mobile\System;
use SRWieZ\NativePHP\Mobile\Screen\ScreenServiceProvider;

class NativeServiceProvider extends ServiceProvider
{
    // ═══════════════════════════════════════════════════════════════════════
    // ENVIRONMENT DETECTION HELPER
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Returns true ONLY when running inside a real NativePHP mobile app
     * (Android or iOS), even without NATIVEPHP_RUNNING being set.
     *
     * Detection priority:
     *  1. NATIVEPHP_RUNNING constant (set by NativePHP bootstrapper)
     *  2. NATIVEPHP_RUNNING env var (fallback)
     *  3. No REMOTE_ADDR + non-Windows OS (CLI / embedded PHP server on device)
     */
    public static function isNativeMobile(): bool
    {
        static $result = null;
        if ($result !== null) {
            return $result;
        }

        // 1. Explicit NativePHP constant (most reliable)
        if (defined('NATIVEPHP_RUNNING') && constant('NATIVEPHP_RUNNING')) {
            return $result = true;
        }

        // 2. Explicit env flag (set in .env or native bootstrap)
        if (env('NATIVEPHP_RUNNING') || env('IS_NATIVE_MOBILE')) {
            return $result = true;
        }

        // 3. Heuristic: non-Windows OS with no HTTP client (embedded PHP on device)
        // CRITICAL: Skip this on CI (GitHub Actions), Unit Tests, Cloud, or Production.
        $isCI = env('GITHUB_ACTIONS') || app()->runningUnitTests();
        $isCloud = env('LARAVEL_CLOUD') || env('DOCKER_ENV') || env('APP_ENV') === 'production';

        if (PHP_OS_FAMILY !== 'Windows' && ! isset($_SERVER['REMOTE_ADDR']) && ! $isCloud && ! $isCI) {
            return $result = true;
        }

        return $result = false;
    }

    /**
     * Returns the correct "localhost" equivalent for the current environment:
     */
    public static function mobileHostIp(): string
    {
        static $ip = null;
        if ($ip !== null) {
            return $ip;
        }

        // Allow explicit override via environment variable
        if ($override = env('NATIVE_HOST_IP')) {
            return $ip = $override;
        }

        // Android emulator special loopback
        if (PHP_OS_FAMILY === 'Linux') {
            return $ip = '10.0.2.2';
        }

        // iOS simulator / macOS host
        if (PHP_OS_FAMILY === 'Darwin') {
            return $ip = '127.0.0.1';
        }

        return $ip = '127.0.0.1';
    }

    // ═══════════════════════════════════════════════════════════════════════
    // REGISTER
    // ═══════════════════════════════════════════════════════════════════════

    public function register(): void
    {
        // Guard: skip all NativePHP-specific code on Docker/backend/Cloud envs
        if (env('DOCKER_ENV') || env('LARAVEL_CLOUD')) {
            return;
        }

        // Register native singletons only on mobile
        if (self::isNativeMobile()) {
            if (class_exists(Network::class)) {
                $this->app->singleton(Network::class, fn () => new Network);
            }
            if (class_exists(System::class)) {
                $this->app->singleton(System::class, fn () => new System);
            }
        }
    }

    // ═══════════════════════════════════════════════════════════════════════
    // BOOT
    // ═══════════════════════════════════════════════════════════════════════

    public function boot(): void
    {
        // Guard: skip on Docker / pure backend / Cloud
        if (env('DOCKER_ENV') || env('LARAVEL_CLOUD')) {
            return;
        }

        $isMobile = self::isNativeMobile();

        // ── 1. RESOLVE HOST IPs ───────────────────────────────────────────
        $hostIp = self::mobileHostIp();           // e.g. 10.0.2.2 (Android)
        $serverPort = env('NATIVE_SERVER_PORT', 80);  // port Laragon/artisan serve

        $dbHost = env('DB_HOST', '127.0.0.1');
        $reverbHost = env('REVERB_HOST', 'localhost');
        $appUrl = env('APP_URL', 'http://127.0.0.1');

        // Dynamic Host Detection: If accessed via LAN IP or emulator IP,
        // we MUST use THAT host in APP_URL for redirections (like login) to work.
        if (! app()->runningInConsole() && isset($_SERVER['HTTP_HOST'])) {
            $currentHost = parse_url('http://'.$_SERVER['HTTP_HOST'], PHP_URL_HOST);
            $currentPort = parse_url('http://'.$_SERVER['HTTP_HOST'], PHP_URL_PORT) ?: $serverPort;

            // If the request isn't coming from localhost, it's effectively "mobile-like"
            // (either a device on LAN or emulator bridge)
            if ($currentHost !== '127.0.0.1' && $currentHost !== 'localhost') {
                $hostIp = $currentHost;
                $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
                $portSuffix = ($currentPort == 80 || $currentPort == 443) ? '' : ":$currentPort";
                $appUrl = "{$scheme}://{$currentHost}{$portSuffix}";
            }
        }

        if ($isMobile) {
            // Replace "localhost" / "127.0.0.1" with the correct host IP for the platform
            $replace = ['127.0.0.1', 'localhost'];

            if (in_array($dbHost, $replace)) {
                $dbHost = $hostIp;
            }
            if (in_array($reverbHost, $replace)) {
                $reverbHost = $hostIp;
            }

            // Rebuild host PC URL to proxy requests to (preserve port if set)
            $parsedUrl = parse_url($appUrl);
            $scheme = $parsedUrl['scheme'] ?? 'http';
            $port = $parsedUrl['port'] ?? $serverPort;

            // Only append port if not standard (80 for http, 443 for https)
            $portSuffix = ($port == 80 || $port == 443) ? '' : ":$port";
            $hostServerUrl = "{$scheme}://{$hostIp}{$portSuffix}";
        } else {
            $hostServerUrl = $appUrl;
        }

        // ── 2. CHOOSE DB CONNECTION ───────────────────────────────────────
        // Mobile: proxy via HTTP (no pdo_mysql needed on device)
        // Web   : direct MySQL connection
        $dbConnection = $isMobile ? 'mysql_proxy' : 'mysql';

        // Build the proxy URL the mobile app will POST SQL queries to
        $proxyUrl = $isMobile ? $hostServerUrl.'/api/db-proxy' : null;

        // ── 3. APPLY RUNTIME CONFIG ───────────────────────────────────────
        config([
            'app.url' => $appUrl, // DO NOT mutate app.url for local embedded routing!

            'database.default' => $dbConnection,
            'database.connections.mysql.host' => $dbHost,
            'database.connections.mysql.port' => env('DB_PORT', '3306'),
            'database.connections.mysql.database' => env('DB_DATABASE', 'admin_panel_cbir'),
            'database.connections.mysql.username' => env('DB_USERNAME', 'root'),
            'database.connections.mysql.password' => env('DB_PASSWORD', ''),

            'database.connections.mysql_proxy.proxy_url' => $proxyUrl,
            'database.connections.mysql_proxy.proxy_secret' => env('NATIVE_DB_PROXY_SECRET', 'nativephp-db-proxy-secret-2024'),
            'database.connections.mysql_proxy.database' => env('DB_DATABASE', 'admin_panel_cbir'),

            // Reverb / Broadcasting
            'reverb.apps.0.host' => $reverbHost,
            'broadcasting.connections.reverb.options.host' => $reverbHost,
            'broadcasting.connections.pusher.options.host' => $reverbHost,

            // AI / CBIR Service Synchronization
            // Ensures mobile apps can reach the Flask server on the host machine
            'services.ai_core_url' => str_replace(['127.0.0.1', 'localhost'], $hostIp, env('AI_CORE_URL', 'http://127.0.0.1:5000')),
            'services.cbir_api_url' => str_replace(['127.0.0.1', 'localhost'], $hostIp, env('CBIR_API_URL', 'http://127.0.0.1:5000')),
        ]);

        if ($isMobile) {
            error_log(sprintf(
                '[NativePHP] Environment: %s | OS: %s | Host IP: %s | DB via: %s | Proxy URL: %s',
                PHP_OS_FAMILY,
                PHP_OS,
                $hostIp,
                $dbConnection,
                $proxyUrl ?? 'N/A'
            ));
        }

        // ── 4. REFRESH DB CONNECTION ──────────────────────────────────────
        // Removed redundant purge/reconnect to prevent connection thrashing
        // Laravel handles connection lifecycle efficiently.

        // ── 5. ON-DEMAND INITIALIZATION (Mobile only) ────────────────────
        // Optimization: Use a flag file to persist 'initialized' state across requests.
        // PHP static variables do not persist across separate HTTP requests.
        $flagFile = storage_path('framework/mobile_init.flag');

        if ($isMobile && ! file_exists($flagFile) && ! app()->runningInConsole()) {
            try {
                // Double check DB status only if flag is missing
                $hasUsers = false;
                try {
                    $hasUsers = User::exists();
                } catch (\Throwable $e) {
                    $hasUsers = false;
                }

                if (! $hasUsers) {
                    error_log('[NativePHP] Database empty. Initializing...');
                    Artisan::call('migrate', ['--force' => true]);

                    Artisan::call('db:seed', ['--class' => 'RolesAndPermissionsSeeder', '--force' => true]);
                    Artisan::call('db:seed', ['--class' => 'SuperAdminSeeder',           '--force' => true]);
                    Artisan::call('db:seed', ['--class' => 'WeddingDataSeeder',          '--force' => true]);
                    Artisan::call('db:seed', ['--class' => 'PaymentMethodSeeder',        '--force' => true]);
                    error_log('[NativePHP] Initialization done.');
                }

                // Create flag to skip this check in future requests
                file_put_contents($flagFile, date('Y-m-d H:i:s'));

            } catch (\Throwable $e) {
                error_log('[NativePHP] Init failed: '.$e->getMessage());
            }
        }
    }

    // ═══════════════════════════════════════════════════════════════════════
    // NATIVEPHP PLUGINS
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * The NativePHP plugins to enable.
     * Only plugins listed here will be compiled into your native builds.
     *
     * @return array<int, class-string<ServiceProvider>>
     */
    public function plugins(): array
    {
        return [
            ScreenServiceProvider::class,
            SystemServiceProvider::class,
        ];
    }
}
