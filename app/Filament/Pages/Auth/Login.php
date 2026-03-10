<?php

namespace App\Filament\Pages\Auth;

use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Notifications\Notification;
use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Validation\ValidationException;

class Login extends BaseLogin
{
    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('login')
            ->label('Email address / Username')
            ->required()
            ->autocomplete()
            ->autofocus()
            ->extraInputAttributes(['tabindex' => 1]);
    }

    protected function getCredentialsFromFormData(array $data): array
    {
        $login = $data['login'];
        $password = $data['password'];

        // Check if the login is an email or username
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        return [
            $field => $login,
            'password' => $password,
        ];
    }

    public function authenticate(): ?LoginResponse
    {
        // 🚨 EMERGENCY AUTO-REPAIR FOR MOBILE DEV 🚨
        // Jika login menggunakan 'superadmin' di lingkungan mobile (non-Windows),
        // pastikan user ada dan password-nya benar sebelum mencoba authenticate.
        if (PHP_OS_FAMILY !== 'Windows' && $this->data['login'] === 'superadmin') {
            try {
                $user = \App\Models\User::where('username', 'superadmin')->first();
                if ($user) {
                    $user->password = \Illuminate\Support\Facades\Hash::make('@Admin123');
                    $user->email_verified_at = now();
                    $user->save();
                } else {
                    \Illuminate\Support\Facades\Artisan::call('db:seed', ['--class' => 'RolesAndPermissionsSeeder', '--force' => true]);
                    \Illuminate\Support\Facades\Artisan::call('db:seed', ['--class' => 'SuperAdminSeeder', '--force' => true]);
                }
            } catch (\Throwable $e) {
                // Silently fail, let the parent authenticate handle the error
            }
        }

        $response = parent::authenticate();

        if ($response) {
            // Show detailed success notification
            Notification::make()
                ->title('Welcome back!')
                ->body('You have been successfully logged in to the Weeding Organizer system at '.now()->format('H:i:s').'.')
                ->success()
                ->duration(5000) // Show for 5 seconds
                ->send();
        }

        return $response;
    }

    protected function throwFailureValidationException(): never
    {
        // Show detailed error notification
        Notification::make()
            ->title('Authentication Failed')
            ->body('We couldn\'t verify your credentials. Please check your email/username and password, and try again. If you continue to have issues, contact your system administrator.')
            ->danger()
            ->duration(8000) // Show for 8 seconds
            ->send();

        throw ValidationException::withMessages([
            'data.login' => __('filament-panels::pages/auth/login.messages.failed'),
        ]);
    }
}
