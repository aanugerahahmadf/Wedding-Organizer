<?php

namespace App\Filament\User\Resources\HistoryResource\Pages;

use App\Filament\User\Resources\HistoryResource;
use Filament\Resources\Pages\ListRecords;

class ListHistories extends ListRecords
{
    protected static string $resource = HistoryResource::class;

    public function getTabs(): array
    {
        return [
            'all' => \Filament\Resources\Components\Tab::make(__('Semua'))
                ->badge(\App\Models\History::where('user_id', auth()->id())->count()),
            'order' => \Filament\Resources\Components\Tab::make(__('Pembelian'))
                ->modifyQueryUsing(fn ($query) => $query->where('type', 'order'))
                ->badge(\App\Models\History::where('user_id', auth()->id())->where('type', 'order')->count())
                ->badgeColor('info'),
            'topup' => \Filament\Resources\Components\Tab::make(__('Deposit'))
                ->modifyQueryUsing(fn ($query) => $query->where('type', 'topup'))
                ->badge(\App\Models\History::where('user_id', auth()->id())->where('type', 'topup')->count())
                ->badgeColor('success'),
            'withdrawal' => \Filament\Resources\Components\Tab::make(__('Penarikan'))
                ->modifyQueryUsing(fn ($query) => $query->where('type', 'withdrawal'))
                ->badge(\App\Models\History::where('user_id', auth()->id())->where('type', 'withdrawal')->count())
                ->badgeColor('danger'),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [

        ];
    }
}
