<x-filament-panels::page.simple>
    <x-filament-panels::form wire:submit="verify">
        {{ $this->form }}

        <div class="flex justify-center mt-2">
            <x-filament-panels::form.actions
                :actions="$this->getCachedFormActions()"
                :full-width="$this->hasFullWidthFormActions()"
            />
        </div>
    </x-filament-panels::form>

    <div class="mt-4 text-center text-sm text-gray-600 dark:text-gray-400">
        {{ __('Tidak menerima email?') }} <br><br>
        {{ $this->resendNotificationAction }}
    </div>
</x-filament-panels::page.simple>
