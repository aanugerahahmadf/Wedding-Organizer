<?php

namespace App\Filament\User\Resources\WishlistResource\Pages;

use App\Filament\User\Resources\WishlistResource;
use Filament\Resources\Pages\ManageRecords;

class ManageWishlists extends ManageRecords
{
    protected static string $resource = WishlistResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
