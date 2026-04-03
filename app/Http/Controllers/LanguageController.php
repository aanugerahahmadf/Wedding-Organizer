<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class LanguageController extends Controller
{
    /**
     * Switch the application language.
     *
     * @param Request $request
     * @param string $locale
     * @return RedirectResponse
     */
    public function switch(Request $request, string $locale): RedirectResponse
    {
        $locals = config('filament-language-switcher.locals', []);
        
        if (array_key_exists($locale, $locals)) {
            // 1. Force update Session
            session()->put('locale', $locale);
            app()->setLocale($locale);

            // 2. Find authenticated user across all defined guards
            $user = null;
            $guards = ['web', 'filament','livewire', 'mobile', 'nativephp', 'admin', 'api'];
            foreach ($guards as $guard) {
                try {
                    if (Auth::guard($guard)->check()) {
                        $user = Auth::guard($guard)->user();
                        break;
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }

            // 3. Persist to Database if user found
            if ($user) {
                // Ensure we use the common ID and Type string format
                \App\Models\UserLanguage::updateOrCreate(
                    ['model_id' => (string) $user->id, 'model_type' => 'App\Models\User'],
                    ['lang' => $locale]
                );
                
                // Nuclear purge of user-specific caches
                cache()->forget("user_lang_{$user->id}");
                cache()->forget("active_trans_map_{$locale}");
            }
        }

        // Final safe check for session
        if ($locale && session()->get('locale') !== $locale) {
            session()->put('locale', $locale);
        }

        return back();
    }
}
