<?php

namespace App\Providers\Filament;

use App\Filament\Auth\Login;
use App\Filament\Auth\OtpEmailVerificationPrompt;
use App\Filament\Auth\OtpRequestPasswordReset;
use App\Filament\Auth\OtpResetPassword;
use App\Filament\Auth\Register;
use App\Filament\Auth\VerifyOtp;
use App\Filament\Pages\Dashboard;
use App\Filament\Pages\EditProfilePage;
use App\Filament\Widgets\OrdersChart;
use App\Filament\Widgets\RecentOrders;
use App\Filament\Widgets\RevenueChart;
use App\Filament\Widgets\StatsOverview;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\SuperAdmin;
use App\Http\Middleware\VerifyCsrfToken;
use Filament\Enums\ThemeMode;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Contracts\View\View;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(Login::class)
            // ->registration(Register::class)
            ->passwordReset(
                OtpRequestPasswordReset::class,
                OtpResetPassword::class
            )
            ->emailVerification(OtpEmailVerificationPrompt::class)
            ->sidebarCollapsibleOnDesktop()
            ->brandName(config('app.name'))
            ->brandLogo(asset('images/logo.png'))
            ->brandLogoHeight('3rem')
            // ->simplePageMaxContentWidth(MaxWidth::Small)
            ->colors([
                'danger' => Color::Rose,
                'gray' => Color::Gray,
                'info' => Color::Blue,
                'primary' => Color::Indigo,
                'success' => Color::Emerald,
                'warning' => Color::Orange,
            ])
            ->defaultThemeMode(ThemeMode::System)
            ->topNavigation()
            ->databaseNotifications()
            ->renderHook(
                'panels::global-search.after',
                fn (): View => view('filament.filament-language-switcher.language-switcher')
            )
            ->renderHook(
                'panels::auth.form.before',
                fn (): View => view('filament.filament-language-switcher.language-switcher')
            )
            ->renderHook(
                'panels::footer',
                fn (): ?View => ! str_contains(request()->route()?->getName() ?? '', 'auth') ? view('filament.footer') : null
            )
            ->renderHook(
                'panels::auth.login.form.after',
                fn (): View => view('filament.footer')
            )
            ->userMenuItems([
                'profile' => MenuItem::make()
                    ->label(fn (): string => Auth::user()?->full_name ?? __('Profil'))
                    ->url(fn (): string => EditProfilePage::getUrl())
                    ->icon('eos-account-circle')
                    ->visible(fn (): bool => Auth::check()),
            ])
            ->navigationGroups([
                NavigationGroup::make()->label(__('Pengguna')),
                NavigationGroup::make()->label(__('Blog & Media')),
                NavigationGroup::make()->label(__('Studio')),
                NavigationGroup::make()->label(__('Transaksi')),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                StatsOverview::class,
                RevenueChart::class,
                OrdersChart::class,
                RecentOrders::class,
            ])
            ->middleware([
                VerifyCsrfToken::class,
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                ShareErrorsFromSession::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                SuperAdmin::class,
            ])
            ->routes(function (Panel $panel): void {
                VerifyOtp::registerRoutes($panel);
            });
    }
}
