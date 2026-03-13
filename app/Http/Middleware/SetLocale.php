<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = session()->get('locale');

        // Jika user login, sinkronisasi dengan preferensi di Database (ORM)
        if (Auth::check()) {
            $user = Auth::user();
            
            // Cek apakah model user punya kolom 'lang'
            if (isset($user->lang)) {
                $locale = $user->lang;
            } else {
                // Cari di tabel UserLanguage (Morph ORM)
                $userLang = \App\Models\UserLanguage::where('model_type', $user->getMorphClass())
                    ->where('model_id', $user->id)
                    ->first();
                
                if ($userLang) {
                    $locale = $userLang->lang;
                }
            }
        }

        // Jika masih kosong, coba deteksi dari browser (Auto-adjust to country)
        if (!$locale) {
            $locale = $request->getPreferredLanguage(array_keys(config('filament-language-switcher.locals', ['id', 'en'])));
        }

        if ($locale) {
            app()->setLocale($locale);
            session()->put('locale', $locale);
        }

        return $next($request);
    }
}
