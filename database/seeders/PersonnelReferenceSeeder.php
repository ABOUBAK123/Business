<?php

namespace Database\Seeders;

use App\Models\IssuingAdministration;
use App\Models\PersonnelLeaveType;
use App\Models\PersonnelTraining;
use App\Models\RecipientAdministration;
use Illuminate\Database\Seeder;

class PersonnelReferenceSeeder extends Seeder
{
    public function run(): void
    {
        $administrations = collect();

        if (class_exists(IssuingAdministration::class)) {
            $administrations = $administrations->merge(
                IssuingAdministration::query()->get(['id'])->map(fn ($item) => [
                    'type' => 'emitter',
                    'id' => $item->id,
                ])
            );
        }

        if (class_exists(RecipientAdministration::class)) {
            $administrations = $administrations->merge(
                RecipientAdministration::query()->get(['id'])->map(fn ($item) => [
                    'type' => 'recipient',
                    'id' => $item->id,
                ])
            );
        }

        $leaveTypes = [
            ['code' => 'ANNUAL', 'name' => 'Congé annuel', 'unit' => 'day', 'default_days' => 30, 'carry_over_days' => 5, 'requires_attachment' => false, 'is_paid' => true],
            ['code' => 'SICK', 'name' => 'Congé maladie', 'unit' => 'day', 'default_days' => 15, 'carry_over_days' => 0, 'requires_attachment' => true, 'is_paid' => true],
            ['code' => 'MATERNITY', 'name' => 'Congé maternité / paternité', 'unit' => 'day', 'default_days' => 90, 'carry_over_days' => 0, 'requires_attachment' => true, 'is_paid' => true],
            ['code' => 'SPECIAL', 'name' => 'Permission exceptionnelle', 'unit' => 'day', 'default_days' => 5, 'carry_over_days' => 0, 'requires_attachment' => false, 'is_paid' => true],
            ['code' => 'UNPAID', 'name' => 'Congé sans solde', 'unit' => 'day', 'default_days' => 10, 'carry_over_days' => 0, 'requires_attachment' => false, 'is_paid' => false],
        ];

        $trainings = [
            ['code' => 'ONBOARDING', 'title' => 'Parcours d\'intégration agent', 'category' => 'Intégration', 'delivery_mode' => 'internal', 'duration_hours' => 8, 'is_mandatory' => true, 'provider_name' => 'RH interne', 'skills' => ['Culture organisationnelle', 'Procédures internes']],
            ['code' => 'ARCHIVE', 'title' => 'Gestion documentaire et archivage', 'category' => 'Métier', 'delivery_mode' => 'internal', 'duration_hours' => 6, 'is_mandatory' => false, 'provider_name' => 'Centre de formation interne', 'skills' => ['Archivage', 'Conformité documentaire']],
            ['code' => 'LEAD', 'title' => 'Leadership et management d\'équipe', 'category' => 'Management', 'delivery_mode' => 'hybrid', 'duration_hours' => 14, 'is_mandatory' => false, 'provider_name' => 'Cabinet RH', 'skills' => ['Leadership', 'Communication managériale']],
            ['code' => 'DIGITAL', 'title' => 'Bureautique et collaboration numérique', 'category' => 'Digital', 'delivery_mode' => 'elearning', 'duration_hours' => 10, 'is_mandatory' => false, 'provider_name' => 'Plateforme e-learning', 'skills' => ['Suite bureautique', 'Collaboration numérique']],
        ];

        foreach ($administrations as $administration) {
            foreach ($leaveTypes as $leaveType) {
                PersonnelLeaveType::query()->updateOrCreate(
                    [
                        'administration_type' => $administration['type'],
                        'administration_id' => $administration['id'],
                        'code' => $leaveType['code'],
                    ],
                    [
                        'name' => $leaveType['name'],
                        'description' => $leaveType['name'],
                        'unit' => $leaveType['unit'],
                        'default_days' => $leaveType['default_days'],
                        'carry_over_days' => $leaveType['carry_over_days'],
                        'requires_attachment' => $leaveType['requires_attachment'],
                        'is_paid' => $leaveType['is_paid'],
                        'is_active' => true,
                    ]
                );
            }

            foreach ($trainings as $training) {
                PersonnelTraining::query()->updateOrCreate(
                    [
                        'administration_type' => $administration['type'],
                        'administration_id' => $administration['id'],
                        'code' => $training['code'],
                    ],
                    [
                        'title' => $training['title'],
                        'category' => $training['category'],
                        'provider_name' => $training['provider_name'],
                        'delivery_mode' => $training['delivery_mode'],
                        'duration_hours' => $training['duration_hours'],
                        'budget_amount' => null,
                        'validity_months' => null,
                        'is_mandatory' => $training['is_mandatory'],
                        'is_active' => true,
                        'description' => $training['title'],
                        'objectives' => 'Référentiel initial chargé automatiquement.',
                        'skills' => $training['skills'],
                    ]
                );
            }
        }
    }
}
