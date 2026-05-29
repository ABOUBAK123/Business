<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Découverte',
                'slug' => 'decouverte',
                'description' => 'Idéal pour démarrer. Gratuit pendant 14 jours.',
                'monthly_price' => 0,
                'annual_price' => 0,
                'max_branches' => 1,
                'max_articles' => 100,
                'max_users' => 2,
                'max_transactions_per_month' => 200,
                'has_advanced_reports' => false,
                'has_api_access' => false,
                'has_priority_support' => false,
                'trial_days' => 14,
                'sort_order' => 1,
            ],
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'description' => 'Pour les petites quincailleries en croissance.',
                'monthly_price' => 15000,
                'annual_price' => 153000,
                'max_branches' => 2,
                'max_articles' => 500,
                'max_users' => 5,
                'max_transactions_per_month' => 2000,
                'has_advanced_reports' => false,
                'has_api_access' => false,
                'has_priority_support' => false,
                'trial_days' => 0,
                'sort_order' => 2,
            ],
            [
                'name' => 'Pro',
                'slug' => 'pro',
                'description' => 'Pour les quincailleries avec plusieurs succursales.',
                'monthly_price' => 35000,
                'annual_price' => 357000,
                'max_branches' => 5,
                'max_articles' => 5000,
                'max_users' => 15,
                'max_transactions_per_month' => -1,
                'has_advanced_reports' => true,
                'has_api_access' => false,
                'has_priority_support' => true,
                'trial_days' => 0,
                'sort_order' => 3,
            ],
            [
                'name' => 'Entreprise',
                'slug' => 'entreprise',
                'description' => 'Solution complète pour les grandes chaînes de quincailleries.',
                'monthly_price' => 0,
                'annual_price' => 0,
                'max_branches' => -1,
                'max_articles' => -1,
                'max_users' => -1,
                'max_transactions_per_month' => -1,
                'has_advanced_reports' => true,
                'has_api_access' => true,
                'has_priority_support' => true,
                'trial_days' => 0,
                'sort_order' => 4,
            ],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::updateOrCreate(['slug' => $plan['slug']], $plan);
        }
    }
}
