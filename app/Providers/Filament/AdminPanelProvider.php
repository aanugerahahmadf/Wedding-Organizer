<?php

namespace App\Providers\Filament;

use Filament\Enums\ThemeMode;
use App\Livewire\PersonalInfoComponent;
use App\Livewire\UsernameComponent;
use App\Livewire\MobileSettingsComponent;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Navigation\NavigationGroup;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\MaxWidth;
use Filament\View\PanelsRenderHook;
use Filament\Widgets;
use Illuminate\Contracts\View\View;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Jeddsaliba\FilamentMessages\FilamentMessagesPlugin;
use Joaopaulolndev\FilamentEditProfile\FilamentEditProfilePlugin;
use Joaopaulolndev\FilamentEditProfile\Pages\EditProfilePage;
use App\Http\Middleware\VerifyCsrfToken;
use App\Http\Middleware\SuperAdmin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(\App\Filament\Pages\Auth\Login::class)
            ->passwordReset(\App\Filament\Pages\Auth\OtpRequestPasswordReset::class)
            ->emailVerification(\App\Filament\Pages\Auth\OtpEmailVerificationPrompt::class)
            ->sidebarFullyCollapsibleOnDesktop()
            ->brandName(config('app.name'))
            ->simplePageMaxContentWidth(MaxWidth::Small)
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
            // ->spa()
            ->plugins([
                FilamentEditProfilePlugin::make()
                    ->setTitle('Profile')
                    ->setNavigationLabel('Profile')
                    ->shouldRegisterNavigation(false)
                    ->setIcon('bxs-user-account')
                    ->setSort(10)
                    ->shouldShowEditProfileForm(false)
                    ->shouldShowAvatarForm(
                        value: true,
                        directory: 'avatars',
                        rules: 'mimes:jpeg,png|max:102400000'
                    )
                    ->customProfileComponents([
                        PersonalInfoComponent::class,
                        UsernameComponent::class,
                        MobileSettingsComponent::class,
                    ]),
                FilamentMessagesPlugin::make(),
            ])
            ->userMenuItems([
                'profile' => MenuItem::make()
                    ->label(fn (): string => Auth::user()?->full_name ?? 'Profile')
                    ->url(fn (): string => EditProfilePage::getUrl())
                    ->icon('eos-account-circle')
                    ->visible(fn (): bool => Auth::check()),
            ])
            ->navigationGroups([
                NavigationGroup::make()->label('User'),
                NavigationGroup::make()->label('Blog & Media'),
                NavigationGroup::make()->label('Studio'),
                NavigationGroup::make()->label('Transactions'),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                \App\Filament\Widgets\StatsOverview::class,
                \App\Filament\Widgets\RevenueChart::class,
                \App\Filament\Widgets\OrdersChart::class,
                \App\Filament\Widgets\RecentOrders::class,
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
            ], isPersistent: true)
            ->authMiddleware([
                Authenticate::class,
                SuperAdmin::class,
            ], isPersistent: true);
    }
}
