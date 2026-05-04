<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PersonnelEmployeesTemplateExport implements FromArray, WithHeadings
{
    public function headings(): array
    {
        return [
            'employee_number',
            'first_name',
            'last_name',
            'job_title',
            'email',
            'phone',
            'employment_status',
            'hire_date',
            'administration_type',
            'administration_id',
            'sub_entity_id',
            'superieur_hierarchique_email',
            'marital_status',
            'address',
            'emergency_contact_name',
            'emergency_contact_phone',
            'notes',
        ];
    }

    public function array(): array
    {
        return [
            [
                'MAT-001',
                'Aminata',
                'Kone',
                'Assistante de direction',
                'aminata.kone@example.ci',
                '+2250700000000',
                'active',
                '2025-01-10',
                'emitter',
                'UUID_ADMINISTRATION',
                '',
                'chef.service@example.ci',
                'Mariee',
                'Abidjan',
                'Adama Kone',
                '+2250500000000',
                'Import initial',
            ],
        ];
    }
}
