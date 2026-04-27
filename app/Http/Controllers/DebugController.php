<?php

namespace App\Http\Controllers;

class DebugController extends Controller
{
    public function testLocale()
    {
        return response()->json([
            'app_locale' => app()->getLocale(),
            'session_locale' => session('locale'),
            'config_locale' => config('app.locale'),
            'test_translation' => __('messages.welcome'),
            'all_translations' => [
                'messages' => __('messages'),
                'auth' => __('auth'),
                'buttons' => __('buttons'),
            ],
        ]);
    }
}
