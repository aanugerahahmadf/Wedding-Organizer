<?php

namespace App\Filament\Admin\Resources\OrderResource\Pages;

use App\Filament\Admin\Exports\OrderExporter;
use App\Filament\Admin\Resources\OrderResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;

/**
 * @property-read \App\Filament\Resources\OrderResource $resource
 */
class ManageOrders extends ManageRecords

{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ExportAction::make()
                ->exporter(OrderExporter::class)
                ->label(__('Ekspor Data'))
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success'),
            Actions\CreateAction::make()
                ->label(__('Tambah Order'))
                ->icon('heroicon-o-plus')
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title(__('Order Ditambahkan'))
                        ->body(__('Order baru telah berhasil ditambahkan.'))
                ),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => \Filament\Resources\Components\Tab::make(__('Semua'))
                ->badge(\App\Models\Order::count()),
            'pending' => \Filament\Resources\Components\Tab::make(\App\Enums\OrderStatus::PENDING->getLabel())
                ->icon(\App\Enums\OrderStatus::PENDING->getIcon())
                ->modifyQueryUsing(fn ($query) => $query->where('status', \App\Enums\OrderStatus::PENDING))
                ->badge(\App\Models\Order::where('status', \App\Enums\OrderStatus::PENDING)->count())
                ->badgeColor(\App\Enums\OrderStatus::PENDING->getColor()),
            'confirmed' => \Filament\Resources\Components\Tab::make(\App\Enums\OrderStatus::CONFIRMED->getLabel())
                ->icon(\App\Enums\OrderStatus::CONFIRMED->getIcon())
                ->modifyQueryUsing(fn ($query) => $query->where('status', \App\Enums\OrderStatus::CONFIRMED))
                ->badge(\App\Models\Order::where('status', \App\Enums\OrderStatus::CONFIRMED)->count())
                ->badgeColor(\App\Enums\OrderStatus::CONFIRMED->getColor()),
            'preparing' => \Filament\Resources\Components\Tab::make(\App\Enums\OrderStatus::PREPARING->getLabel())
                ->icon(\App\Enums\OrderStatus::PREPARING->getIcon())
                ->modifyQueryUsing(fn ($query) => $query->where('status', \App\Enums\OrderStatus::PREPARING))
                ->badge(\App\Models\Order::where('status', \App\Enums\OrderStatus::PREPARING)->count())
                ->badgeColor(\App\Enums\OrderStatus::PREPARING->getColor()),
            'event_day' => \Filament\Resources\Components\Tab::make(\App\Enums\OrderStatus::EVENT_DAY->getLabel())
                ->icon(\App\Enums\OrderStatus::EVENT_DAY->getIcon())
                ->modifyQueryUsing(fn ($query) => $query->where('status', \App\Enums\OrderStatus::EVENT_DAY))
                ->badge(\App\Models\Order::where('status', \App\Enums\OrderStatus::EVENT_DAY)->count())
                ->badgeColor(\App\Enums\OrderStatus::EVENT_DAY->getColor()),
            'completed' => \Filament\Resources\Components\Tab::make(\App\Enums\OrderStatus::COMPLETED->getLabel())
                ->icon(\App\Enums\OrderStatus::COMPLETED->getIcon())
                ->modifyQueryUsing(fn ($query) => $query->where('status', \App\Enums\OrderStatus::COMPLETED))
                ->badge(\App\Models\Order::where('status', \App\Enums\OrderStatus::COMPLETED)->count())
                ->badgeColor(\App\Enums\OrderStatus::COMPLETED->getColor()),
            'cancelled' => \Filament\Resources\Components\Tab::make(\App\Enums\OrderStatus::CANCELLED->getLabel())
                ->icon(\App\Enums\OrderStatus::CANCELLED->getIcon())
                ->modifyQueryUsing(fn ($query) => $query->where('status', \App\Enums\OrderStatus::CANCELLED))
                ->badge(\App\Models\Order::where('status', \App\Enums\OrderStatus::CANCELLED)->count())
                ->badgeColor(\App\Enums\OrderStatus::CANCELLED->getColor()),
        ];
    }
}
