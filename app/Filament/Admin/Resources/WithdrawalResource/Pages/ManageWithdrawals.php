<?php

namespace App\Filament\Admin\Resources\WithdrawalResource\Pages;

use App\Filament\Admin\Exports\WithdrawalExporter;
use App\Filament\Admin\Resources\WithdrawalResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

/**
 * @property-read \App\Filament\Resources\WithdrawalResource $resource
 */
class ManageWithdrawals extends ManageRecords
{
    protected static string $resource = WithdrawalResource::class;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make(__('Semua'))
                ->icon('heroicon-m-list-bullet'),
            'pending' => Tab::make(__('Perlu Persetujuan'))
                ->icon('heroicon-m-clock')
                ->badge(fn() => \App\Models\Withdrawal::where('status', \App\Enums\WithdrawalStatus::PENDING)->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', \App\Enums\WithdrawalStatus::PENDING)),
            'completed' => Tab::make(__('Tercairkan'))
                ->icon('heroicon-m-check-badge')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', \App\Enums\WithdrawalStatus::COMPLETED)),
            'rejected' => Tab::make(__('Ditolak'))
                ->icon('heroicon-m-x-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', \App\Enums\WithdrawalStatus::REJECTED)),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ExportAction::make()
                ->exporter(WithdrawalExporter::class)
                ->label(__('Ekspor Data'))
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success'),
            Actions\CreateAction::make()
                ->label(__('Tambah Penarikan'))
                ->icon('heroicon-o-plus')
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title(__('Penarikan Ditambahkan'))
                        ->body(__('Data penarikan baru telah berhasil ditambahkan.'))
                ),
        ];
    }
}
