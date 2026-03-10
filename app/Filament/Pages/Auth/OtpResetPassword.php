<?php

namespace App\Filament\Pages\Auth;

use Filament\Pages\Auth\PasswordReset\ResetPassword as BaseResetPassword;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Filament\Notifications\Notification;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Component;
use Filament\Forms\Form;
use App\Models\User;
use Illuminate\Validation\Rules\Password as PasswordRule;

class OtpResetPassword extends BaseResetPassword
{
    public $otp = '';

    public function mount(?string $email = null, ?string $token = null): void
    {
        if (Filament::auth()->check()) {
            redirect()->intended(Filament::getUrl());
            return;
        }

        $this->email = request()->query('email', '');
        
        $this->form->fill([
            'email' => $this->email,
        ]);
        
        // Filament expects a token, so we just supply a dummy one to satisfy constraints
        $this->token = 'otp';
    }

    public function resetPassword(): ?\Filament\Http\Responses\Auth\Contracts\PasswordResetResponse
    {
        $data = $this->form->getState();
        $email = $data['email'] ?? $this->email;
        $otp = $data['otp'];

        $cachedOtp = Cache::get('password_reset_otp_' . $email);

        if ($cachedOtp && (string) $cachedOtp === (string) $otp) {
            $user = User::where('email', $email)->first();
            if ($user) {
                $user->password = Hash::make($data['password']);
                $user->save();
            }

            Cache::forget('password_reset_otp_' . $email);

            Notification::make()
                ->title('Password reset successfully.')
                ->success()
                ->send();

            $this->redirect(Filament::getLoginUrl());
            return null;
        } else {
            Notification::make()
                ->title('Invalid or expired OTP code.')
                ->danger()
                ->send();
            
            return null;
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                $this->getEmailFormComponent(),
                $this->getOtpFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
            ]);
    }

    protected function getOtpFormComponent(): Component
    {
        return TextInput::make('otp')
            ->label('6-Digit OTP Code')
            ->required()
            ->length(6)
            ->numeric()
            ->autofocus();
    }
    
    protected function getEmailFormComponent(): Component
    {
        // Override to remove autofocus
        $field = parent::getEmailFormComponent();
        $field->autofocus(false);
        return $field;
    }
}
