<?php

namespace App\Filament\User\Pages;


use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $slug = 'home';

    protected static ?string $navigationIcon = 'heroicon-o-home';
    
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
