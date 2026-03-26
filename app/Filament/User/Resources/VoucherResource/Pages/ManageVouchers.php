<?php

namespace App\Filament\User\Resources\VoucherResource\Pages;

use App\Filament\User\Resources\VoucherResource;
use Filament\Resources\Pages\ManageRecords;

class ManageVouchers extends ManageRecords
{
    protected static string $resource = VoucherResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No creation action for user
        ];
    }
}
