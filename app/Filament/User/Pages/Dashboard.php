<?php

namespace App\Filament\User\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use App\Filament\User\Resources\WeddingOrganizerResource;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) \App\Models\WeddingOrganizer::count();
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return static::getNavigationLabel();
    }
    
    public function mount(): void
    {
        // Redirect dashboard to studio profile since it's a one-studio application
        redirect()->to(WeddingOrganizerResource::getUrl('index', ['record' => 1]));
    }
    
    public static function getNavigationGroup(): ?string
    {
        return __('Beranda');
    }

    protected static ?int $navigationSort = 1;

    public static function getNavigationIcon(): ?string
    {
        return static::$navigationIcon;
    }

    public static function getNavigationLabel(): string
    {
        return __('Beranda');
    }

    public function getWidgets(): array
    {
        return [
            \App\Filament\User\Widgets\StatsOverview::class,
            \App\Filament\User\Widgets\UserOrdersChart::class,
            \App\Filament\User\Widgets\UserSpendingChart::class,
            \App\Filament\User\Widgets\LatestBookings::class,
        ];
    }

    public function getTitle(): string
    {
        return __('Beranda');
    }
}
