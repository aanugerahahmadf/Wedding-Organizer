<?php

namespace App\Filament\User\Resources\TopupResource\Pages;

use App\Filament\User\Resources\TopupResource;
use Filament\Resources\Pages\ManageRecords;

class ManageTopups extends ManageRecords
{
    protected static string $resource = TopupResource::class;

    protected function getHeaderActions(): array
    {
        return [
 
        ];
    }

}
