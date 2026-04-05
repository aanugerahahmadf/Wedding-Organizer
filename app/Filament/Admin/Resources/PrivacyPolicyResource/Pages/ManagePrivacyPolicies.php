<?php

namespace App\Filament\Admin\Resources\PrivacyPolicyResource\Pages;

use App\Filament\Admin\Resources\PrivacyPolicyResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManagePrivacyPolicies extends ManageRecords
{
    protected static string $resource = PrivacyPolicyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
