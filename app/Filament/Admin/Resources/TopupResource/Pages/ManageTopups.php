<?php

namespace App\Filament\Admin\Resources\TopupResource\Pages;

use App\Filament\Admin\Exports\TopupExporter;
use App\Filament\Admin\Resources\TopupResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

/**
 * @property-read \App\Filament\Resources\TopupResource $resource
 */
class ManageTopups extends ManageRecords
{
    protected static string $resource = TopupResource::class;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make(__('Semua'))
                ->icon('heroicon-m-list-bullet'),
            'pending' => Tab::make(__('Perlu Verifikasi'))
                ->icon('heroicon-m-clock')
                ->badge(fn() => \App\Models\Topup::where('status', \App\Enums\TopupStatus::PENDING)->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', \App\Enums\TopupStatus::PENDING)),
            'success' => Tab::make(__('Berhasil'))
                ->icon('heroicon-m-check-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', \App\Enums\TopupStatus::SUCCESS)),
            'failed' => Tab::make(__('Gagal/Batal'))
                ->icon('heroicon-m-x-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', [\App\Enums\TopupStatus::FAILED, \App\Enums\TopupStatus::CANCELLED])),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ExportAction::make()
                ->exporter(TopupExporter::class)
                ->label(__('Ekspor Data'))
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success'),
            Actions\CreateAction::make()
                ->label(__('Tambah Topup'))
                ->icon('heroicon-o-plus')
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title(__('Topup Ditambahkan'))
                        ->body(__('Data topup baru telah berhasil ditambahkan.'))
                ),
        ];
    }
}
