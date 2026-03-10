<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;


    protected function getStats(): array
    {
        // Helper to get trend data for the last 10 days
        $getTrend = function ($model) {
            $data = $model::where('created_at', '>=', now()->subDays(10))
                ->selectRaw('date(created_at) as date, count(*) as count')
                ->groupBy('date')
                ->pluck('count', 'date')
                ->toArray();

            return collect(range(9, 0))
                ->map(fn ($days) => $data[now()->subDays($days)->format('Y-m-d')] ?? 0)
                ->toArray();
        };

        $userCounts = $getTrend(\App\Models\User::class);
        $orderCounts = $getTrend(\App\Models\Order::class);

        // Calculate Revenue Trend (Last 10 days vs previous 10 days)
        $revenueData = \App\Models\Order::where('payment_status', 'paid')
            ->where('created_at', '>=', now()->subDays(10))
            ->selectRaw('date(created_at) as date, sum(total_price) as sum')
            ->groupBy('date')
            ->pluck('sum', 'date')
            ->toArray();
            
        $revenueCounts = collect(range(9, 0))
            ->map(fn ($days) => (float) ($revenueData[now()->subDays($days)->format('Y-m-d')] ?? 0))
            ->toArray();

        $totalRevenue = \App\Models\Order::where('payment_status', 'paid')->sum('total_price');
        $thisMonthRevenue = \App\Models\Order::where('payment_status', 'paid')
            ->where('created_at', '>=', now()->startOfMonth())
            ->sum('total_price');

        // Growth indicators
        $newUserCount = \App\Models\User::where('created_at', '>=', now()->subDays(7))->count();
        $newOrderCount = \App\Models\Order::where('created_at', '>=', now()->subDays(7))->count();

        return [
            Stat::make('Total Users', \App\Models\User::count())
                ->description($newUserCount . ' new this week')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->chart($userCounts)
                ->color('success'),

            Stat::make('Total Orders', \App\Models\Order::count())
                ->description($newOrderCount . ' new this week')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->chart($orderCounts)
                ->color('info'),

            Stat::make('Total Revenue', 'IDR ' . number_format($totalRevenue, 0, ',', '.'))
                ->description('IDR ' . number_format($thisMonthRevenue, 0, ',', '.') . ' this month')
                ->descriptionIcon($thisMonthRevenue > 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-banknotes')
                ->chart($revenueCounts)
                ->color('success'),
        ];
    }
}
