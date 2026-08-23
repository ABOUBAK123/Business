<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SettingController extends Controller
{
    private const GROUPS = ['sms', 'whatsapp', 'email', 'mobile_money', 'code_article'];

    private const MOBILE_MONEY_PROVIDERS = ['orange_money', 'mtn_momo', 'wave', 'moov_money'];

    public function index(Request $request): View
    {
        $tab = in_array($request->tab, self::GROUPS) ? $request->tab : 'sms';

        $settings = Setting::group($tab);

        // For code_article: load tenants + all per-tenant configs
        $tenants         = collect();
        $tenantConfigs   = [];
        $selectedTenant  = null;

        if ($tab === 'code_article') {
            $tenants = Tenant::withoutGlobalScopes()
                ->withoutTrashed()
                ->orderBy('shop_name')
                ->get(['id', 'shop_name']);

            $selectedTenant = $request->tenant_id ? (int) $request->tenant_id : null;

            if ($selectedTenant) {
                $suffix = "_t{$selectedTenant}";
                $tenantConfigs = [
                    'article_code_prefix' => Setting::get("article_code_prefix{$suffix}", $settings['article_code_prefix'] ?? 'ART-'),
                    'article_code_length' => Setting::get("article_code_length{$suffix}", $settings['article_code_length'] ?? 6),
                    'article_code_type'   => Setting::get("article_code_type{$suffix}",   $settings['article_code_type']   ?? 'alphanumeric'),
                ];
            }
        }

        return view('super-admin.settings.index', compact(
            'tab', 'settings', 'tenants', 'selectedTenant', 'tenantConfigs'
        ));
    }

    public function update(Request $request, string $group): RedirectResponse
    {
        if (!in_array($group, self::GROUPS)) {
            abort(404);
        }

        // Special handling for code_article: support per-tenant keys
        if ($group === 'code_article') {
            $tenantId = $request->integer('tenant_id') ?: null;
            $suffix   = $tenantId ? "_t{$tenantId}" : '';

            Setting::bulkSet([
                "article_code_prefix{$suffix}" => $request->input('article_code_prefix', 'ART-'),
                "article_code_length{$suffix}" => max(3, min(12, (int) $request->input('article_code_length', 6))),
                "article_code_type{$suffix}"   => $request->input('article_code_type', 'alphanumeric'),
            ], $group);

            $redirect = ['tab' => $group];
            if ($tenantId) $redirect['tenant_id'] = $tenantId;

            return redirect()
                ->route('super-admin.settings.index', $redirect)
                ->with('success', 'Configuration Code article enregistrée.');
        }

        $secrets = $this->secretKeys($group);
        $data    = $request->except(['_token', '_method']);

        foreach ($secrets as $key) {
            if (empty($data[$key])) {
                $data[$key] = Setting::get($key, '');
            }
        }

        foreach ($this->boolKeys($group) as $key) {
            $data[$key] = $request->boolean($key) ? '1' : '0';
        }

        if ($group === 'mobile_money') {
            $this->handleProviderLogoUploads($request);
        }

        Setting::bulkSet($data, $group, $secrets);

        return redirect()
            ->route('super-admin.settings.index', ['tab' => $group])
            ->with('success', 'Configuration ' . $this->groupLabel($group) . ' enregistrée.');
    }

    public function testEmail(Request $request): RedirectResponse
    {
        $request->validate([
            'test_email' => 'required|email',
        ]);

        $settings = Setting::group('email');
        $this->applyMailSettings($settings);

        $fromName = $settings['mail_from_name'] ?? config('app.name');

        try {
            Mail::raw(
                "Ceci est un email de test envoyé depuis la configuration de {$fromName}.\n\n"
                    . "Si vous recevez ce message, votre configuration email fonctionne correctement.",
                function ($message) use ($request, $fromName) {
                    $message->to($request->string('test_email')->toString())
                        ->subject('Test de configuration email — ' . $fromName);
                }
            );
        } catch (\Throwable $e) {
            return redirect()
                ->route('super-admin.settings.index', ['tab' => 'email'])
                ->withErrors(['test_email' => "Échec de l'envoi : " . $e->getMessage()]);
        }

        return redirect()
            ->route('super-admin.settings.index', ['tab' => 'email'])
            ->with('success', 'Email de test envoyé à ' . $request->input('test_email') . '.');
    }

    private function applyMailSettings(array $settings): void
    {
        $driver = $settings['mail_driver'] ?? 'smtp';

        config(['mail.default' => $driver]);

        if ($driver === 'smtp') {
            config([
                'mail.mailers.smtp.host' => $settings['mail_host'] ?? null,
                'mail.mailers.smtp.port' => $settings['mail_port'] ?? 587,
                'mail.mailers.smtp.username' => $settings['mail_username'] ?? null,
                'mail.mailers.smtp.password' => $settings['mail_password'] ?? null,
                'mail.mailers.smtp.encryption' => ($settings['mail_encryption'] ?? '') ?: null,
            ]);
        }

        config([
            'mail.from.address' => ($settings['mail_from_address'] ?? '') ?: config('mail.from.address'),
            'mail.from.name' => ($settings['mail_from_name'] ?? '') ?: config('mail.from.name'),
        ]);

        // Force the mailer to rebuild using the config just applied above.
        app()->forgetInstance('mail.manager');
        app()->forgetInstance('mailer');
    }

    private function handleProviderLogoUploads(Request $request): void
    {
        foreach (self::MOBILE_MONEY_PROVIDERS as $provider) {
            $field = "{$provider}_logo";

            if (! $request->hasFile($field)) {
                continue;
            }

            $request->validate([
                $field => 'image|mimes:png,jpg,jpeg,svg,webp|max:1024',
            ]);

            $previousPath = Setting::get("{$provider}_logo_path");
            if ($previousPath) {
                Storage::disk('public')->delete($previousPath);
            }

            $path = $request->file($field)->store('payment-logos', 'public');
            Setting::set("{$provider}_logo_path", $path, 'mobile_money', false);
        }
    }

    private function secretKeys(string $group): array
    {
        return match ($group) {
            'sms'          => ['sms_api_key', 'sms_api_secret'],
            'whatsapp'     => ['whatsapp_token', 'whatsapp_webhook_secret'],
            'email'        => ['mail_password'],
            'mobile_money' => [
                'orange_money_api_secret', 'mtn_momo_api_secret',
                'wave_api_key', 'wave_webhook_secret', 'moov_money_api_key',
            ],
            'code_article' => [],
            default        => [],
        };
    }

    private function boolKeys(string $group): array
    {
        return match ($group) {
            'sms'          => ['sms_enabled'],
            'whatsapp'     => ['whatsapp_enabled'],
            'email'        => ['mail_enabled'],
            'mobile_money' => [
                'orange_money_enabled', 'mtn_momo_enabled',
                'wave_enabled', 'moov_money_enabled',
            ],
            default        => [],
        };
    }

    private function groupLabel(string $group): string
    {
        return match ($group) {
            'sms'          => 'SMS',
            'whatsapp'     => 'WhatsApp',
            'email'        => 'Email',
            'mobile_money' => 'Mobile Money',
            'code_article' => 'Code article',
            default        => $group,
        };
    }
}
