<?php

namespace App\Livewire;

use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Joaopaulolndev\FilamentEditProfile\Concerns\HasSort;
use Livewire\Component;
use Native\Mobile\Facades\System;
use Illuminate\Contracts\View\View;

class MobileSettingsComponent extends Component implements HasForms
{
    use HasSort;
    use InteractsWithForms;

    protected static int $sort = 4;

    public function openSettings(): void
    {
        System::openAppSettings();
    }

    public function render(): View
    {
        return view('livewire.mobile-settings-component');
    }
}
