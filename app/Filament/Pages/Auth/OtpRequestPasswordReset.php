<?php

namespace App\Filament\Pages\Auth;

use Filament\Pages\Auth\PasswordReset\RequestPasswordReset as BaseRequestPasswordReset;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Filament\Notifications\Notification;
use App\Models\User;

class OtpRequestPasswordReset extends BaseRequestPasswordReset
{
    public function request(): void
    {
        $data = $this->form->getState();
        $email = $data['email'];

        $user = User::where('email', $email)->first();

        // Send OTP via email using Cache
        if ($user) {
            $otp = random_int(100000, 999999);
            Cache::put('password_reset_otp_' . $email, $otp, now()->addMinutes(15));

            Mail::send('emails.otp', [
                'title' => 'Password Reset',
                'description' => 'We received a request to reset your password. Please use the verification code below to proceed. This code is valid for 15 minutes.',
                'otp' => $otp,
            ], function ($message) use ($email) {
                $message->to($email)->subject('Password Reset Code');
            });
        }

        Notification::make()
            ->title('If an account exists, a password reset code has been sent.')
            ->success()
            ->send();

        $this->redirect(OtpResetPassword::getUrl() . '?email=' . urlencode($email));
    }
}
