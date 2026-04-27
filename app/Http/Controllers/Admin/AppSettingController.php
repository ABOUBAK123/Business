<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AppSettingController extends Controller
{
    public function index()
    {
        $settings = AppSetting::all()->keyBy('key');
        return view('admin.settings', compact('settings'));
    }

    /** Clés autorisées à être modifiées via ce contrôleur. */
    private const ALLOWED_KEYS = [
        'app_name', 'app_public_url', 'app_logo',
        'onlyoffice_server_url', 'onlyoffice_secret', 'onlyoffice_doc_viewer',
        'mail_from_address', 'mail_from_name',
        'qr_image_page', 'qr_image_x', 'qr_image_y', 'qr_image_width', 'qr_image_height',
        'signature_qr_position',
        'theme_primary_color', 'theme_secondary_color', 'theme_logo',
        'courrier_archival_days',
        'email_notifications_enabled', 'email_notifications_from',
        'chat_enabled', 'chat_scope',
    ];

    public function update(Request $request)
    {
        // Réservé aux administrateurs
        abort_if(
            !auth()->check() || auth()->user()->role !== 'admin',
            403,
            'Accès réservé aux administrateurs.'
        );

        $filtered = array_filter(
            $request->except('_token', '_method'),
            fn($key) => in_array($key, self::ALLOWED_KEYS, true),
            ARRAY_FILTER_USE_KEY
        );

        foreach ($filtered as $key => $value) {
            AppSetting::updateOrCreate(
                ['key' => $key],
                ['id' => Str::uuid(), 'value' => $value]
            );
        }
        return back()->with('success', 'Paramètres enregistrés.');
    }

    public function get(string $key)
    {
        $setting = AppSetting::where('key', $key)->first();
        return response()->json(['value' => $setting?->value]);
    }
}
