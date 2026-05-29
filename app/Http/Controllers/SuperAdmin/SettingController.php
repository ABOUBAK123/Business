<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    private const GROUPS = ['sms', 'whatsapp', 'email', 'mobile_money', 'code_article'];

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

        Setting::bulkSet($data, $group, $secrets);

        return redirect()
            ->route('super-admin.settings.index', ['tab' => $group])
            ->with('success', 'Configuration ' . $this->groupLabel($group) . ' enregistrée.');
    }

    private function secretKeys(string $group): array
    {
        return match ($group) {
            'sms'          => ['sms_api_key', 'sms_api_secret'],
            'whatsapp'     => ['whatsapp_token', 'whatsapp_webhook_secret'],
            'email'        => ['mail_password'],
            'mobile_money' => [
                'orange_money_api_secret', 'mtn_momo_api_secret',
                'wave_api_key', 'moov_money_api_key',
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
