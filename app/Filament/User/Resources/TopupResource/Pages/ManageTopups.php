<?php

namespace App\Filament\User\Resources\TopupResource\Pages;

use App\Filament\User\Resources\TopupResource;
use Filament\Resources\Pages\ManageRecords;

class ManageTopups extends ManageRecords
{
    protected static string $resource = TopupResource::class;

    public function getTabs(): array
    {
        return [
            'all' => \Filament\Resources\Components\Tab::make(__('Semua'))
                ->badge(\App\Models\Topup::where('user_id', auth()->id())->count()),
            'pending' => \Filament\Resources\Components\Tab::make(__('Menunggu'))
                ->modifyQueryUsing(fn ($query) => $query->where('status', \App\Enums\TopupStatus::PENDING))
                ->badge(\App\Models\Topup::where('user_id', auth()->id())->where('status', \App\Enums\TopupStatus::PENDING)->count())
                ->badgeColor('warning'),
            'success' => \Filament\Resources\Components\Tab::make(__('Berhasil'))
                ->modifyQueryUsing(fn ($query) => $query->where('status', \App\Enums\TopupStatus::SUCCESS))
                ->badge(\App\Models\Topup::where('user_id', auth()->id())->where('status', \App\Enums\TopupStatus::SUCCESS)->count())
                ->badgeColor('success'),
            'failed' => \Filament\Resources\Components\Tab::make(__('Gagal'))
                ->modifyQueryUsing(fn ($query) => $query->whereIn('status', [\App\Enums\TopupStatus::FAILED, \App\Enums\TopupStatus::CANCELLED]))
                ->badge(\App\Models\Topup::where('user_id', auth()->id())->whereIn('status', [\App\Enums\TopupStatus::FAILED, \App\Enums\TopupStatus::CANCELLED])->count())
                ->badgeColor('danger'),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
 
        ];
    }

}
