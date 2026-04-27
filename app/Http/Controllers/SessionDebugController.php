<?php
/**
 * Test de Session - Vérifier que la session persiste après changement de langue
 * Accès: GET http://localhost/test/session-debug
 */

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SessionDebugController extends Controller
{
    public function index(Request $request)
    {
        $locale = session('locale');
        $appLocale = app()->getLocale();

        return response()->json([
            'step' => 'Before change',
            'session_locale' => $locale,
            'app_locale' => $appLocale,
            'session_id' => session()->getId(),
            'instructions' => [
                '1. Note the session_locale and session_id above',
                '2. Change language via profile menu to English',
                '3. Manually refresh this page',
                '4. Check if session_locale changed to "en"',
                '5. Check if session_id stayed the same (confirms session persisted)'
            ]
        ]);
    }

    public function setLocaleTest(Request $request, $locale)
    {
        $valid = ['fr', 'en', 'es', 'pt', 'ar'];
        if (!in_array($locale, $valid)) {
            return response()->json(['error' => 'Invalid locale'], 400);
        }

        session(['locale' => $locale]);
        app()->setLocale($locale);

        return response()->json([
            'status' => 'Locale changed',
            'session_locale' => session('locale'),
            'app_locale' => app()->getLocale(),
            'session_id' => session()->getId(),
            'next_step' => 'Refresh the page and check /test/session-debug'
        ]);
    }
}
