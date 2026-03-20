<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $sessionLocale = (string) session()->get('locale');
        $locale = $sessionLocale ?: null;

        // Force check across all guards
        $user = null;
        $guards = ['web', 'filament', 'admin', 'api'];
        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                $user = Auth::guard($guard)->user();
                break; // Found the user
            }
        }

        if ($user) {
            // Get current DB locale via accessor
            $dbLocale = $user->lang; 

            if ($sessionLocale && $sessionLocale !== $dbLocale) {
                // SINKRON: Session changed (Welcome page switch). Persist to DB.
                \App\Models\UserLanguage::updateOrCreate(
                    ['model_id' => (string) $user->id, 'model_type' => 'App\Models\User'],
                    ['lang' => $sessionLocale]
                );
                $locale = $sessionLocale;
            } elseif ($dbLocale) {
                // SINKRON: DB changed (from another device/session). Persist to Session.
                $locale = $dbLocale;
            }
        }

        // Detect from browser if everything else fails
        if (!$locale) {
            $supported = array_keys(config('filament-language-switcher.locals', []));
            $locale = $request->getPreferredLanguage($supported ?: ['id', 'en']);
        }

        if ($locale) {
            app()->setLocale($locale);
            session()->put('locale', (string) $locale);
            
            // Extreme force for Filament context
            config(['app.locale' => $locale]);
        }

        return $next($request);
    }
}
