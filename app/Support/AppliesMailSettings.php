<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Applies the Super Admin-configured email settings (Setting model, group
 * "email") to the runtime mail config, so Mail::/Mailable sends actually use
 * the SMTP credentials configured in the admin panel instead of the static
 * .env mailer.
 */
trait AppliesMailSettings
{
    protected function mailSendingEnabled(): bool
    {
        return Setting::get('mail_enabled', '0') === '1';
    }

    protected function applyMailSettings(): void
    {
        $settings = Setting::group('email');
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
}
