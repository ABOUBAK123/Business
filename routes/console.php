<?php

use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Services\Payments\MtnMomoCollectionGateway;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('mtn:test-sandbox
    {--scenario=* : Noms des scenarios a executer}
    {--poll-seconds=90 : Delai max d attente du statut final}
    {--interval=5 : Intervalle de polling en secondes}
    {--currency=EUR : Devise utilisee pour les paiements de test}
    {--keep-records : Conserver les enregistrements en base}
    {--dry-run : Afficher uniquement la matrice des scenarios}', function (MtnMomoCollectionGateway $gateway) {
    $allScenarios = [
        [
            'name' => 'success-default',
            'phone' => '46733123470',
            'expected' => 'success',
            'note' => 'Numero hors table de cas specifiques: attendu SUCCESSFUL',
        ],
        [
            'name' => 'payer-failed',
            'phone' => '46733123450',
            'expected' => 'failed',
            'note' => 'Scenario sandbox RequestToPayPayerFailed',
        ],
        [
            'name' => 'payer-rejected',
            'phone' => '46733123451',
            'expected' => 'failed',
            'note' => 'Scenario sandbox RequestToPayPayerRejected',
        ],
        [
            'name' => 'payer-expired',
            'phone' => '46733123452',
            'expected' => 'failed',
            'note' => 'Scenario sandbox RequestToPayPayerExpired',
        ],
        [
            'name' => 'payer-ongoing',
            'phone' => '46733123453',
            'expected' => 'pending',
            'note' => 'Scenario sandbox RequestToPayPayerOngoing',
        ],
        [
            'name' => 'payer-delayed',
            'phone' => '46733123454',
            'expected' => 'pending',
            'note' => 'Scenario sandbox RequestToPayPayerDelayed',
        ],
        [
            'name' => 'payer-not-found',
            'phone' => '46733123455',
            'expected' => 'failed',
            'note' => 'Scenario sandbox RequestToPayPayerNotFound',
        ],
    ];

    $selectedNames = array_values(array_filter((array) $this->option('scenario')));
    $selected = $allScenarios;
    if ($selectedNames !== []) {
        $selected = array_values(array_filter(
            $allScenarios,
            static fn (array $scenario) => in_array($scenario['name'], $selectedNames, true)
        ));
    }

    if ($selected === []) {
        $this->error('Aucun scenario valide selectionne.');
        $this->line('Scenarios disponibles: ' . implode(', ', array_column($allScenarios, 'name')));
        return self::FAILURE;
    }

    $this->info('Matrice des scenarios MTN sandbox');
    $this->table(
        ['scenario', 'phone', 'expected', 'note'],
        array_map(static fn (array $s) => [$s['name'], $s['phone'], $s['expected'], $s['note']], $selected)
    );

    if ($this->option('dry-run')) {
        $this->comment('Dry-run actif: aucun appel MTN execute.');
        return self::SUCCESS;
    }

    $pollSeconds = max(10, (int) $this->option('poll-seconds'));
    $intervalSeconds = max(2, (int) $this->option('interval'));
    $currency = strtoupper((string) $this->option('currency'));
    $keepRecords = (bool) $this->option('keep-records');

    $results = [];
    $hasFailure = false;

    foreach ($selected as $scenario) {
        $plan = SubscriptionPlan::query()->first();
        if (! $plan) {
            $plan = SubscriptionPlan::create([
                'name' => 'Plan Sandbox Test',
                'slug' => 'plan-sandbox-test-' . Str::lower(Str::random(6)),
                'description' => 'Plan temporaire pour tests MTN sandbox',
                'monthly_price' => 1000,
                'annual_price' => 10000,
                'max_branches' => 1,
                'max_articles' => 100,
                'max_users' => 2,
                'max_transactions_per_month' => 200,
                'is_active' => true,
                'sort_order' => 999,
            ]);
        }

        $tenant = Tenant::create([
            'subscription_plan_id' => $plan->id,
            'shop_name' => 'Sandbox MTN Test ' . Str::upper(Str::random(4)),
            'slug' => 'sandbox-mtn-' . Str::lower(Str::random(10)),
            'status' => 'trial',
            'currency' => $currency,
            'tax_rate' => 18,
            'country' => 'CI',
            'theme_color' => '#1e40af',
        ]);

        $subscription = Subscription::create([
            'tenant_id' => $tenant->id,
            'subscription_plan_id' => $plan->id,
            'billing_cycle' => 'monthly',
            'amount_paid' => 1000,
            'status' => 'grace',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        $payment = SubscriptionPayment::create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $subscription->id,
            'amount' => 1000,
            'currency' => $currency,
            'method' => 'mobile_money',
            'provider' => 'mtn_momo',
            'reference' => (string) Str::uuid(),
            'status' => 'pending',
            'metadata' => [
                'sandbox_scenario' => $scenario['name'],
                'sandbox_phone' => $scenario['phone'],
            ],
        ]);

        $initiation = $gateway->initiate($payment, $scenario['phone']);

        if (! ($initiation['success'] ?? false)) {
            $hasFailure = true;
            $results[] = [
                'scenario' => $scenario['name'],
                'phone' => $scenario['phone'],
                'expected' => $scenario['expected'],
                'actual' => 'initiation_failed',
                'status' => 'FAILED',
                'pass' => 'NO',
                'message' => (string) ($initiation['message'] ?? 'initiation_failed'),
            ];

            if (! $keepRecords) {
                $payment->delete();
                $subscription->delete();
                method_exists($tenant, 'forceDelete') ? $tenant->forceDelete() : $tenant->delete();
            }

            continue;
        }

        $deadline = now()->addSeconds($pollSeconds);
        $finalStatus = 'UNKNOWN';
        $rawReason = '';

        do {
            $verification = $gateway->verify($payment->fresh());

            if ($verification['success'] ?? false) {
                $finalStatus = strtoupper((string) ($verification['status'] ?? 'UNKNOWN'));
                $rawReason = (string) (
                    data_get($verification, 'raw.reason')
                    ?? data_get($verification, 'raw.errorCode')
                    ?? ''
                );

                if (in_array($finalStatus, ['SUCCESSFUL', 'SUCCESS', 'SUCCESSFUL_PAYMENT', 'FAILED', 'REJECTED'], true)) {
                    break;
                }
            }

            if (now()->gte($deadline)) {
                break;
            }

            sleep($intervalSeconds);
        } while (true);

        $actualCategory = match (true) {
            in_array($finalStatus, ['SUCCESSFUL', 'SUCCESS', 'SUCCESSFUL_PAYMENT'], true) => 'success',
            in_array($finalStatus, ['FAILED', 'REJECTED'], true) => 'failed',
            default => 'pending',
        };

        $isPass = $actualCategory === $scenario['expected'];
        if (! $isPass) {
            $hasFailure = true;
        }

        $results[] = [
            'scenario' => $scenario['name'],
            'phone' => $scenario['phone'],
            'expected' => $scenario['expected'],
            'actual' => $actualCategory,
            'status' => $finalStatus,
            'pass' => $isPass ? 'YES' : 'NO',
            'message' => $rawReason !== '' ? $rawReason : '-',
        ];

        if (! $keepRecords) {
            $payment->delete();
            $subscription->delete();
            method_exists($tenant, 'forceDelete') ? $tenant->forceDelete() : $tenant->delete();
        }
    }

    $this->newLine();
    $this->info('Resultats des tests MTN sandbox');
    $this->table(['scenario', 'phone', 'expected', 'actual', 'status', 'pass', 'message'], $results);

    if ($hasFailure) {
        $this->error('Au moins un scenario a echoue.');
        return self::FAILURE;
    }

    $this->info('Tous les scenarios ont le comportement attendu.');
    return self::SUCCESS;
})->purpose('Executer des tests de scenarios MTN MoMo sandbox (RequestToPay) avec assertions attendues');
