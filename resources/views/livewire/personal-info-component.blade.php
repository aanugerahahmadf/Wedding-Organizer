<div>
    <form wire:submit="save" class="fi-sc-form">
        {{ $this->form }}

        <div class="flex justify-end mt-4">
            <x-filament::button type="submit">
                {{ __('filament-edit-profile::default.save') }}
            </x-filament::button>
        </div>
    </form>

    <x-filament-actions::modals />
</div>
