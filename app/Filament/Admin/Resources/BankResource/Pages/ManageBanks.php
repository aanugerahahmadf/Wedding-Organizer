<?php

namespace App\Filament\Admin\Resources\BankResource\Pages;

use App\Filament\Admin\Exports\BankExporter;
use App\Filament\Admin\Resources\BankResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageBanks extends ManageRecords
{
    protected static string $resource = BankResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ExportAction::make()
                ->exporter(BankExporter::class)
                ->label(__('Ekspor Data'))
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success'),
            Actions\CreateAction::make(),
        ];
    }
}
