<?php

namespace App\Console\Commands;

use App\Mail\SubscriptionExpiringMail;
use App\Models\SubscriptionReminderLog;
use App\Models\Tenant;
use App\Support\AppliesMailSettings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendSubscriptionExpiryReminders extends Command
{
    use AppliesMailSettings;

    protected $signature = 'subscriptions:send-expiry-reminders';

    protected $description = "Envoie un email de rappel aux propriétaires dont l'abonnement expire dans 10, 5, 2 ou 1 jour(s)";

    private const THRESHOLDS = [10, 5, 2, 1];

    public function handle(): int
    {
        if (! $this->mailSendingEnabled()) {
            $this->comment("Envoi d'emails désactivé dans Paramètres > Email — aucun rappel envoyé.");
            return self::SUCCESS;
        }

        $this->applyMailSettings();

        $sent = 0;
        $skipped = 0;
        $failed = 0;

        foreach (self::THRESHOLDS as $days) {
            $targetDate = now()->addDays($days)->toDateString();

            $tenants = Tenant::withoutGlobalScopes()
                ->with('owner')
                ->whereIn('status', ['active', 'trial', 'grace'])
                ->whereNotNull('subscription_ends_at')
                ->whereDate('subscription_ends_at', $targetDate)
                ->get();

            foreach ($tenants as $tenant) {
                if (! $tenant->owner?->email) {
                    $skipped++;
                    continue;
                }

                $alreadySent = SubscriptionReminderLog::where('tenant_id', $tenant->id)
                    ->where('days_before', $days)
                    ->whereDate('subscription_ends_at', $targetDate)
                    ->exists();

                if ($alreadySent) {
                    $skipped++;
                    continue;
                }

                try {
                    Mail::to($tenant->owner->email)->send(new SubscriptionExpiringMail($tenant, $days));

                    SubscriptionReminderLog::create([
                        'tenant_id' => $tenant->id,
                        'days_before' => $days,
                        'subscription_ends_at' => $targetDate,
                        'sent_at' => now(),
                    ]);

                    $sent++;
                } catch (\Throwable $e) {
                    Log::warning('subscription_reminder.send_failed', [
                        'tenant_id' => $tenant->id,
                        'days_before' => $days,
                        'error' => $e->getMessage(),
                    ]);
                    $failed++;
                }
            }
        }

        $this->info("Rappels envoyés : {$sent}, ignorés (déjà envoyés) : {$skipped}, échecs : {$failed}.");

        return self::SUCCESS;
    }
}
