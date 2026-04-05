<?php

namespace App\Filament\Admin\Resources\TermsOfServiceResource\Pages;

use App\Filament\Admin\Resources\TermsOfServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageTermsOfServices extends ManageRecords
{
    protected static string $resource = TermsOfServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
