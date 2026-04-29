<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Priorité: préférence utilisateur en base > session > config.
        $locale = null;
        $user = Auth::user();
        if ($user && !empty($user->locale)) {
            $locale = (string) $user->locale;
            $request->session()->put('locale', $locale);
        }

        if (!$locale) {
            $locale = $request->session()->get('locale', config('app.locale', 'fr'));
        }

        $validLocales = ['fr', 'en', 'es', 'pt', 'ar'];
        if (!in_array($locale, $validLocales)) {
            $locale = config('app.locale', 'fr');
        }

        // Appliquer la locale AVANT de traiter la requête
        app()->setLocale($locale);

        return $next($request);
    }
}
