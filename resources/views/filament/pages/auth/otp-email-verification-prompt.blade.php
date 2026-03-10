<x-filament-panels::page.simple>
    <x-slot name="subheading">
        We have sent a 6-digit verification code to your email. Please check your inbox and enter the code below.
    </x-slot>

    <x-filament-panels::form wire:submit="verify">
        {{ $this->form }}

        <x-filament-panels::form.actions
            :actions="$this->getCachedFormActions()"
            :full-width="$this->hasFullWidthFormActions()"
        />
    </x-filament-panels::form>

    <div class="mt-4 text-center text-sm text-gray-600 dark:text-gray-400">
        Didn't receive the email? <br><br>
        {{ $this->resendNotificationAction }}
    </div>
</x-filament-panels::page.simple>
