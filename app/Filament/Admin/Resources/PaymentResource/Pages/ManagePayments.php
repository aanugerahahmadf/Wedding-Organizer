<?php

namespace App\Filament\Admin\Resources\PaymentResource\Pages;

use App\Filament\Admin\Exports\PaymentExporter;
use App\Filament\Admin\Resources\PaymentResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

/**
 * @property-read \App\Filament\Resources\PaymentResource $resource
 */
class ManagePayments extends ManageRecords
{
    protected static string $resource = PaymentResource::class;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make(__('Semua'))
                ->icon('heroicon-m-list-bullet'),
            'pending' => Tab::make(__('Perlu Verifikasi'))
                ->icon('heroicon-m-clock')
                ->badge(fn() => \App\Models\Payment::where('status', \App\Enums\PaymentStatus::PENDING)->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', \App\Enums\PaymentStatus::PENDING)),
            'success' => Tab::make(__('Berhasil'))
                ->icon('heroicon-m-check-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', \App\Enums\PaymentStatus::SUCCESS)),
            'failed' => Tab::make(__('Gagal/Batal'))
                ->icon('heroicon-m-x-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', [\App\Enums\PaymentStatus::FAILED, \App\Enums\PaymentStatus::CANCELLED])),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ExportAction::make()
                ->exporter(PaymentExporter::class)
                ->label(__('Ekspor Data'))
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success'),
            Actions\CreateAction::make()
                ->label(__('Tambah Pembayaran'))
                ->icon('heroicon-o-plus')
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title(__('Pembayaran Ditambahkan'))
                        ->body(__('Data pembayaran baru telah berhasil ditambahkan.'))
                ),
        ];
    }
}
