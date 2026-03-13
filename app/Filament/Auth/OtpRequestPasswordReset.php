<?php

namespace App\Filament\Auth;

use Filament\Pages\Auth\PasswordReset\RequestPasswordReset as BaseRequestPasswordReset;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use App\Models\User;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Component;

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
                'title' => 'Atur Ulang Kata Sandi',
                'description' => 'Kami menerima permintaan untuk mengatur ulang kata sandi Anda. Silakan gunakan kode verifikasi di bawah ini untuk melanjutkan. Kode ini berlaku selama 15 menit.',
                'otp' => $otp,
            ], function ($message) use ($email) {
                $message->to($email)->subject('Kode Atur Ulang Kata Sandi');
            });
        }

        Notification::make()
            ->title(__('Jika akun tersedia, kode pengaturan ulang kata sandi telah dikirim.'))
            ->success()
            ->send();

        // Redirect ke halaman Verifikasi OTP dulu
        $this->redirect(\App\Filament\Auth\VerifyOtp::getUrl([
            'email' => $email,
        ]));
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label(__('Email address'))
            ->email()
            ->required()
            ->autocomplete()
            ->autofocus();
    }
}
