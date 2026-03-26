<?php

namespace App\Filament\User\Resources\OrderResource\Pages;

use App\Filament\User\Resources\OrderResource;
use Filament\Resources\Pages\ManageRecords;

class ManageOrders extends ManageRecords
{
    protected static string $resource = OrderResource::class;

    public function getTabs(): array
    {
        return [
            'all' => \Filament\Resources\Components\Tab::make(__('Semua'))
                ->badge(\App\Models\Order::where('user_id', auth()->id())->count()),
            'pending' => \Filament\Resources\Components\Tab::make(__('Belum Bayar'))
                ->modifyQueryUsing(fn ($query) => $query->where('status', \App\Enums\OrderStatus::PENDING))
                ->badge(\App\Models\Order::where('user_id', auth()->id())->where('status', \App\Enums\OrderStatus::PENDING)->count())
                ->badgeColor('warning'),
            'processing' => \Filament\Resources\Components\Tab::make(__('Diproses'))
                ->modifyQueryUsing(fn ($query) => $query->where('status', \App\Enums\OrderStatus::CONFIRMED))
                ->badge(\App\Models\Order::where('user_id', auth()->id())->where('status', \App\Enums\OrderStatus::CONFIRMED)->count())
                ->badgeColor('info'),
            'completed' => \Filament\Resources\Components\Tab::make(__('Selesai'))
                ->modifyQueryUsing(fn ($query) => $query->where('status', \App\Enums\OrderStatus::COMPLETED))
                ->badge(\App\Models\Order::where('user_id', auth()->id())->where('status', \App\Enums\OrderStatus::COMPLETED)->count())
                ->badgeColor('success'),
            'cancelled' => \Filament\Resources\Components\Tab::make(__('Dibatalkan'))
                ->modifyQueryUsing(fn ($query) => $query->where('status', \App\Enums\OrderStatus::CANCELLED))
                ->badge(\App\Models\Order::where('user_id', auth()->id())->where('status', \App\Enums\OrderStatus::CANCELLED)->count())
                ->badgeColor('danger'),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            // No creation action for user
        ];
    }
}
