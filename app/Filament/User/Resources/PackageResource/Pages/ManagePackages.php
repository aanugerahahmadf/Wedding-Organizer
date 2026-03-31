<?php

namespace App\Filament\User\Resources\PackageResource\Pages;

use App\Filament\User\Resources\PackageResource;
use Filament\Resources\Pages\ManageRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ManagePackages extends ManageRecords
{
    protected static string $resource = PackageResource::class;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make(__('Semua Layanan'))
                ->icon('heroicon-m-squares-2x2'),
            'wishlist' => Tab::make(__('Favorit Saya'))
                ->icon('heroicon-m-heart')
                ->badge(fn() => \App\Models\Package::whereHas('wishlists', fn ($q) => $q->where('user_id', auth()->id()))->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('wishlists', fn ($q) => $q->where('user_id', auth()->id()))),
            'orders' => Tab::make(__('Pesanan Saya'))
                ->icon('heroicon-m-shopping-bag')
                ->badge(fn() => \App\Models\Package::whereHas('orders', fn ($q) => $q->where('user_id', auth()->id()))->count())
                ->badgeColor('info')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('orders', fn ($q) => $q->where('user_id', auth()->id()))),
            'payments' => Tab::make(__('Konfirmasi Bayar'))
                ->icon('heroicon-m-credit-card')
                ->badge(fn() => \App\Models\Package::whereHas('orders', fn ($q) => $q->where('user_id', auth()->id())->whereIn('status', [\App\Enums\OrderStatus::PENDING]))->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('orders', fn ($q) => $q->where('user_id', auth()->id())->whereIn('status', [\App\Enums\OrderStatus::PENDING]))),
            'history' => Tab::make(__('Riwayat'))
                ->icon('heroicon-m-clock')
                ->badge(fn() => \App\Models\Package::whereHas('orders', fn ($q) => $q->where('user_id', auth()->id())->whereIn('status', [\App\Enums\OrderStatus::COMPLETED, \App\Enums\OrderStatus::CANCELLED]))->count())
                ->badgeColor('gray')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('orders', fn ($q) => $q->where('user_id', auth()->id())->whereIn('status', [\App\Enums\OrderStatus::COMPLETED, \App\Enums\OrderStatus::CANCELLED]))),
        ];
    }

    protected function modifyQueryUsing(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        if ($ids = session()->get('cbir_results')) {
            return $query->whereIn('id', $ids)
                ->orderByRaw('FIELD(id, ' . implode(',', $ids) . ')');
        }
        return $query;
    }

    public function bookNow($id)
    {
        // For instant booking, we redirect to catalog and trigger the 'book' table action or a custom flow
        // For simplicity now, we'll notify and open the catalog with filter applied
        session()->put('cbir_results', [$id]);
        return redirect()->to(PackageResource::getUrl('index'));
    }

    public function toggleWishlist($id)
    {
        $wishlist = \App\Models\Wishlist::updateOrCreate([
            'user_id' => auth()->id(),
            'package_id' => $id,
        ]);
        
        \Filament\Notifications\Notification::make()
            ->title(__('Berhasil disimpan!'))
            ->success()
            ->send();
    }

    protected function getListeners(): array
    {
        return [
            'refresh_catalog' => '$refresh',
            'book_now' => 'bookNow',
            'toggle_wishlist' => 'toggleWishlist',
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            // Removed for table integration
        ];
    }
}
