<?php

namespace App\Filament\Resources\WishlistResource\Pages;

use App\Filament\Resources\WishlistResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageWishlists extends ManageRecords
{
    protected static string $resource = WishlistResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ExportAction::make()
                ->exporter(\App\Filament\Exports\WishlistExporter::class)
                ->label('Ekspor Data')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success'),
            Actions\CreateAction::make()
                ->label('Tambah Wishlist')
                ->icon('heroicon-o-plus')
                ->successNotification(
                    \Filament\Notifications\Notification::make()
                        ->success()
                        ->title('Wishlist Ditambahkan')
                        ->body('Wishlist baru telah berhasil ditambahkan.')
                ),
        ];
    }
}
