<?php

namespace App\Filament\Pages\Auth;

use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Auth\EmailVerification\EmailVerificationPrompt as BasePrompt;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Filament\Notifications\Notification;
use Filament\Actions\Action;
use Illuminate\Contracts\Support\Htmlable;

class OtpEmailVerificationPrompt extends BasePrompt
{
    protected static string $view = 'filament.pages.auth.otp-email-verification-prompt';

    public ?array $data = [];

    public function mount(): void
    {
        if (Filament::auth()->check() && $this->getVerifiable()->hasVerifiedEmail()) {
            redirect()->intended(Filament::getUrl());
            return;
        }

        $this->form->fill();

        $userId = Filament::auth()->id();
        if ($userId && !Cache::has('otp_sent_' . $userId)) {
            $this->sendEmailVerificationNotification($this->getVerifiable());
            Cache::put('otp_sent_' . $userId, true, now()->addMinutes(15));
        }
    }

    protected function sendEmailVerificationNotification(MustVerifyEmail $user): void
    {
        if ($user->hasVerifiedEmail()) {
            return;
        }

        $otp = random_int(100000, 999999);
        Cache::put('otp_' . $user->id, $otp, now()->addMinutes(15));

        Mail::send('emails.otp', [
            'title' => 'Email Verification',
            'description' => 'We received a request to verify your email address. Please use the following code to complete the verification process. This code is valid for 15 minutes.',
            'otp' => $otp,
        ], function ($message) use ($user) {
            $message->to($user->email)->subject('Email Verification Code');
        });
    }

    public function verify(): void
    {
        $data = $this->form->getState();
        $user = $this->getVerifiable();

        $cachedOtp = Cache::get('otp_' . $user->id);

        if ($cachedOtp && (string) $cachedOtp === (string) $data['otp']) {
            $user->markEmailAsVerified();
            Cache::forget('otp_' . $user->id);
            Cache::forget('otp_sent_' . $user->id);

            Notification::make()->title('Email verified successfully!')->success()->send();

            $this->redirect(Filament::getUrl());
        } else {
            Notification::make()
                ->title('Invalid or expired verification code.')
                ->danger()
                ->send();
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('otp')
                    ->label('Verification Code')
                    ->placeholder('Enter 6-digit code')
                    ->required()
                    ->length(6)
                    ->numeric()
                    ->autofocus(),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('verify')
                ->label('Verify')
                ->submit('verify'),
        ];
    }

    protected function hasFullWidthFormActions(): bool
    {
        return true;
    }

    public function getHeading(): string|Htmlable
    {
        return "Verify your Email";
    }
}
