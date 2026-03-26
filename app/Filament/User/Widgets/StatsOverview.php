<?php

namespace App\Filament\User\Widgets;

use App\Models\Order;
use App\Models\Wishlist;
use App\Enums\OrderStatus;
use Filament\Support\Enums\IconPosition;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $user = Auth::user();
        $name = $user->full_name ?? $user->username ?? __('User');

        return [
            Stat::make(__('Selamat Datang,'), $name)
                ->description(__('Ayo buat momen spesialmu hari ini!'))
                ->descriptionIcon('heroicon-m-sparkles')
                ->color('primary'),

            Stat::make(__('Saldo Dompet'), 'Rp ' . number_format($user->balance ?? 0, 0, ',', '.'))
                ->description(__('Sisa saldo Anda'))
                ->descriptionIcon('heroicon-m-wallet', IconPosition::Before)
                ->color('success')
                ->extraAttributes([
                    'class' => 'cursor-pointer hover:scale-105 transition-transform',
                    'onclick' => "window.location.href='/user/isibalance'",
                ]),

            Stat::make(__('Pesanan Saya'), Order::where('user_id', $user->id)->count())
                ->description(__('Lacak transaksi Anda'))
                ->descriptionIcon('heroicon-m-shopping-bag', IconPosition::Before)
                ->color('info')
                ->extraAttributes([
                    'class' => 'cursor-pointer hover:scale-105 transition-transform',
                    'onclick' => "window.location.href='/user/my-orders'",
                ]),

            Stat::make(__('Favorit'), Wishlist::where('user_id', $user->id)->count())
                ->description(__('Layanan tersimpan'))
                ->descriptionIcon('heroicon-m-heart', IconPosition::Before)
                ->color('danger')
                ->extraAttributes([
                    'class' => 'cursor-pointer hover:scale-105 transition-transform',
                    'onclick' => "window.location.href='/user/wishlists'",
                ]),
        ];
    }
}
