<?php

namespace App\Filament\Auth;

use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Auth\EmailVerification\EmailVerificationPrompt as BasePrompt;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Filament\Notifications\Notification;
use Filament\Actions\Action;
use Filament\Forms\Components\ViewField;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Illuminate\Contracts\Support\Htmlable;
use App\Models\User;

class OtpEmailVerificationPrompt extends \Filament\Pages\Auth\EmailVerification\EmailVerificationPrompt
{
    use \Filament\Pages\Concerns\InteractsWithFormActions;

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

        /** @var User $user */
        $otp = random_int(100000, 999999);
        Cache::put('otp_' . $user->id, $otp, now()->addMinutes(15));

        try {
            Mail::send('emails.otp', [
                'title' => __('Verifikasi Email'),
                'description' => __('Kami menerima permintaan untuk memverifikasi alamat email Anda. Silakan gunakan kode berikut untuk menyelesaikan proses verifikasi. Kode ini berlaku selama 15 menit.'),
                'otp' => $otp,
            ], function ($message) use ($user) {
                $message->to($user->email)->subject(__('Kode Verifikasi Email'));
            });
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Gagal kirim email verifikasi ke {$user->email}: " . $e->getMessage());
        }
    }

    public function verify(): void
    {
        $data = $this->form->getState();
        /** @var User $user */
        $user = $this->getVerifiable();

        $cachedOtp = Cache::get('otp_' . $user->id);

        if ($cachedOtp && (string) $cachedOtp === (string) $data['otp']) {
            $user->markEmailAsVerified();
            Cache::forget('otp_' . $user->id);
            Cache::forget('otp_sent_' . $user->id);

            Notification::make()->title(__('Email berhasil diverifikasi!'))->success()->send();

            $this->redirect(Filament::getUrl());
        } else {
            Notification::make()
                ->title(__('Kode verifikasi tidak valid atau telah kadaluarsa.'))
                ->danger()
                ->send();
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                ViewField::make('otp')
                    ->label(__('Kode Verifikasi'))
                    ->view('filament.auth.otp-field')
                    ->required(),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('verify')
                ->label(__('Verifikasi'))
                ->submit('verify'),
        ];
    }

    protected function hasFullWidthFormActions(): bool
    {
        return false;
    }

    public function getHeading(): string|Htmlable
    {
        return __("Verifikasi Email Anda");
    }

    public function getSubheading(): string
    {
        return __('Silakan masukkan 6 digit kode verifikasi yang telah kami kirimkan ke alamat email Anda.');
    }

    public function resendNotificationAction(): Action
    {
        return Action::make('resendNotification')
            ->label(__('Kirim ulang kode'))
            ->color('gray')
            ->action(function () {
                $this->sendEmailVerificationNotification($this->getVerifiable());

                Notification::make()
                    ->title(__('Kode verifikasi baru telah dikirim.'))
                    ->success()
                    ->send();
            });
    }
}
