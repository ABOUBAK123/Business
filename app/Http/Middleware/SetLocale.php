<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // La session est disponible ici car on est dans le groupe web (après StartSession)
        $locale = $request->session()->get('locale', config('app.locale', 'fr'));

        $validLocales = ['fr', 'en', 'es', 'pt', 'ar'];
        if (!in_array($locale, $validLocales)) {
            $locale = config('app.locale', 'fr');
        }

        // Appliquer la locale AVANT de traiter la requête
        app()->setLocale($locale);

        return $next($request);
    }
}
