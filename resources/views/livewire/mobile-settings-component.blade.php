<div>
    <x-filament::section
        aside
        icon="heroicon-o-device-phone-mobile"
        heading="App Settings"
        description="Buka pengaturan aplikasi untuk memberikan izin (kamera, lokasi, dll) jika sebelumnya ditolak."
    >
        <div class="flex items-center gap-x-3">
             <x-filament::button
                wire:click="openSettings"
                color="primary"
                icon="heroicon-o-cog-6-tooth"
                size="sm"
            >
                Buka Pengaturan HP
            </x-filament::button>
        </div>
    </x-filament::section>
</div>
