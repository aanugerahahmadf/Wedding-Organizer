<?php

namespace App\Filament\User\Resources\PackageResource\Pages;

use App\Filament\User\Resources\PackageResource;
use Filament\Resources\Pages\ManageRecords;

class ManagePackages extends ManageRecords
{
    protected static string $resource = PackageResource::class;

    protected function modifyQueryUsing(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        if ($ids = session()->get('cbir_results')) {
            return $query->whereIn('id', $ids)
                ->orderByRaw('FIELD(id, ' . implode(',', $ids) . ')');
        }
        return $query;
    }

    public function bookNow($id)
    {
        // For instant booking, we redirect to catalog and trigger the 'book' table action or a custom flow
        // For simplicity now, we'll notify and open the catalog with filter applied
        session()->put('cbir_results', [$id]);
        return redirect()->to(PackageResource::getUrl('index'));
    }

    public function toggleWishlist($id)
    {
        $wishlist = \App\Models\Wishlist::updateOrCreate([
            'user_id' => auth()->id(),
            'package_id' => $id,
        ]);
        
        \Filament\Notifications\Notification::make()
            ->title(__('Berhasil disimpan!'))
            ->success()
            ->send();
    }

    protected function getListeners(): array
    {
        return [
            'refresh_catalog' => '$refresh',
            'book_now' => 'bookNow',
            'toggle_wishlist' => 'toggleWishlist',
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            // Removed for table integration
        ];
    }
}
