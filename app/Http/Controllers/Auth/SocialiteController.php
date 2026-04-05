<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Filament\Notifications\Notification;

class SocialiteController extends Controller
{
    public function redirect(string $provider)
    {
        return Socialite::driver($provider)->stateless()->redirect();
    }

    public function callback(string $provider)
    {
        try {
            $socialUser = Socialite::driver($provider)->stateless()->user();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Social Login Error: ' . $e->getMessage());
            Notification::make()->title(__('Gagal Masuk'))->body(__('Gagal mengambil data dari :provider.', ['provider' => ucfirst($provider)]))->danger()->send();
            return redirect()->route('filament.user.auth.login');
        }

        // Prioritize lightning-fast social identification
        $user = User::where('social_id', $socialUser->getId())
                    ->where('social_type', $provider)
                    ->first() ?? User::where('email', $socialUser->getEmail())->first();

        if ($user) {
            // Update existing user with social info if not present
            if (!$user->social_id) {
                $user->update([
                    'social_id' => $socialUser->getId(),
                    'social_type' => $provider,
                ]);
            }
        } else {
            // Check for username duplicate
            $username = $socialUser->getNickname() ?? explode('@', $socialUser->getEmail())[0];
            $originalUsername = $username;
            $count = 1;

            while (User::where('username', $username)->exists()) {
                $username = $originalUsername . $count++;
            }

            // Create a new user
            try {
                $user = User::create([
                    'full_name' => $socialUser->getName() ?? $username,
                    'username' => $username,
                    'email' => $socialUser->getEmail(),
                    'social_id' => $socialUser->getId(),
                    'social_type' => $provider,
                    'avatar_url' => $socialUser->getAvatar(),
                    'email_verified_at' => now(),
                    'active_status' => true,
                    'password' => null, // Password made nullable in migration
                ]);

                // Assign default role if Spatie Permission is available
                if (method_exists($user, 'assignRole')) {
                    // Check if role 'user' exists first
                    if (\Spatie\Permission\Models\Role::where('name', 'user')->exists()) {
                         $user->assignRole('user');
                    }
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('User Creation Error: ' . $e->getMessage());
                Notification::make()->title(__('Gagal Mendaftarkan Akun'))->body(__('Terjadi kesalahan saat mendaftarkan akun Anda.'))->danger()->send();
                return redirect()->route('filament.user.auth.login');
            }
        }

        Auth::login($user);

        Notification::make()->title(__('Berhasil Masuk'))->body(__('Selamat datang, :name!', ['name' => $user->full_name]))->success()->send();

        return redirect()->intended('/user');
    }
}
