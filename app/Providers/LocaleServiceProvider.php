<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Http\Middleware\SetLocale;

class LocaleServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Appliquer la locale dès le démarrage
        $this->setApplicationLocale();
    }

    private function setApplicationLocale(): void
    {
        // Récupérer la locale depuis la session (si disponible)
        $locale = session('locale') ?? config('app.locale', 'fr');

        // Valider la locale
        $validLocales = ['fr', 'en', 'es', 'pt', 'ar'];
        if (!in_array($locale, $validLocales)) {
            $locale = config('app.locale', 'fr');
        }

        // Appliquer la locale
        app()->setLocale($locale);
    }
}
