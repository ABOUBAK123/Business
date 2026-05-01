<?php

namespace App\Services\Templates;

use App\Models\AppSetting;
use App\Models\DocumentTemplate;
use App\Models\IssuingAdministration;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TemplateGenerationCoreService
{
    public function extractContentVariables(string $content): array
    {
        if ($content === '') {
            return [];
        }

        preg_match_all('/\{\{\s*([^{}]+?)\s*\}\}/u', $content, $curlyMatches);
        // Legacy support for existing templates.
        preg_match_all('/\[([^\[\]]+?)\]/u', $content, $legacyMatches);

        $vars = [];
        foreach (array_merge($curlyMatches[1], $legacyMatches[1]) as $rawName) {
            $original = trim((string) $rawName);
            if ($original === '') {
                continue;
            }
            $slug = $this->slugify($original);
            if (!isset($vars[$slug])) {
                $vars[$slug] = $original;
            }
        }

        return $vars;
    }

    public function buildReplacementMap(DocumentTemplate $template, array $contentVars, array $officeVars): array
    {
        $replacements = $contentVars;

        foreach ($template->variables as $variable) {
            $key = (string) $variable->key;
            if ($key === '') {
                continue;
            }
            $replacements[$key] = $variable->label ?: $key;
        }

        foreach ($officeVars as $officeVar) {
            $key = (string) ($officeVar['key'] ?? '');
            $label = (string) ($officeVar['label'] ?? '');
            if ($key === '' || isset($replacements[$key])) {
                continue;
            }
            $replacements[$key] = $label !== '' ? $label : $key;
        }

        return $replacements;
    }

    public function assertRequiredValues(DocumentTemplate $template, array $values): void
    {
        $errors = [];

        foreach ($template->variables as $variable) {
            if (!(bool) $variable->required) {
                continue;
            }

            $key = (string) $variable->key;
            $value = $values[$key] ?? null;
            $isMissing = $value === null || trim((string) $value) === '';

            if ($isMissing) {
                $errors['values.' . $key] = [
                    'Le champ requis "' . ($variable->label ?: $key) . '" est manquant.',
                ];
            }
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
    }

    public function reserveDocumentNumber(DocumentTemplate $template, ?string $userId): array
    {
        $docNumber = null;
        $subEntityCode = null;
        $issuingAdminId = null;

        $targetAdministrationId = (string) ($template->administration_id ?? '');
        if ($userId) {
            $userAdministrationId = (string) (DB::table('users as u')
                ->leftJoin('administration_profiles as ap', 'ap.id', '=', 'u.profile_id')
                ->where('u.id', $userId)
                ->value('ap.administration_id') ?? '');

            if ($userAdministrationId !== '') {
                $targetAdministrationId = $userAdministrationId;
            }
        }

        if ($targetAdministrationId === '') {
            return [
                'document_number' => null,
                'sub_entity_code' => null,
                'issuing_administration_id' => null,
            ];
        }

        $userSubCode = '';
        if ($userId) {
            $userAssignment = DB::table('user_direction_assignments')
                ->where('user_id', $userId)
                ->where('direction_scope_id', $targetAdministrationId)
                ->first();

            $userSubCode = $userAssignment ? strtoupper((string) ($userAssignment->sub_entity_code ?? '')) : '';
        }

        DB::transaction(function () use (
            $targetAdministrationId,
            $userSubCode,
            &$docNumber,
            &$subEntityCode,
            &$issuingAdminId
        ): void {
            $admin = IssuingAdministration::lockForUpdate()->find($targetAdministrationId);
            if (!$admin) {
                return;
            }

            $currentYear = now()->year;
            $adminCode = strtoupper((string) ($admin->code ?: 'ADM'));
            $subEntityCode = $userSubCode !== '' ? $userSubCode : strtoupper((string) ($admin->sub_entity_code ?: ''));
            $issuingAdminId = $admin->id;

            $counterScope = strtolower(str_replace(' ', '_', (string) $subEntityCode));
            $counterKey = 'doc_counter_' . $admin->id . '_' . $counterScope . '_' . $currentYear;

            $setting = AppSetting::lockForUpdate()->where('key', $counterKey)->first();
            if ($setting) {
                $seq = (int) $setting->value + 1;
                $setting->update(['value' => (string) $seq]);
            } else {
                $seq = 1;
                AppSetting::create([
                    'key' => $counterKey,
                    'value' => '1',
                    'description' => 'Compteur documents ' . $adminCode . ($subEntityCode ? ' / ' . $subEntityCode : '') . ' ' . $currentYear,
                ]);
            }

            if ($subEntityCode !== '') {
                $docNumber = sprintf('%s - %s - %05d - %d', $adminCode, $subEntityCode, $seq, $currentYear);
            } else {
                $docNumber = sprintf('%s - %05d - %d', $adminCode, $seq, $currentYear);
            }
        });

        return [
            'document_number' => $docNumber,
            'sub_entity_code' => $subEntityCode,
            'issuing_administration_id' => $issuingAdminId,
        ];
    }

    public function buildAutoValues(array $values, ?string $docNumber, string $verifyUrl, string $responsibleName): array
    {
        $today = now()->locale('fr')->isoFormat('D MMMM YYYY');

        $autoDefaults = [
            'date_du_jour' => $today,
            'date_today' => $today,
            'date' => $today,
            'aujourd_hui' => $today,
            'date_generation' => $today,
            'nom_responsable' => $responsibleName,
            'responsable' => $responsibleName,
            'signataire' => $responsibleName,
            'nom_signataire' => $responsibleName,
            'document_number' => $docNumber ?? '',
            'qr_verify_url' => $verifyUrl,
        ];

        $merged = array_merge($autoDefaults, array_filter($values, static fn ($v) => $v !== null && $v !== ''));
        $merged['document_number'] = $docNumber ?? '';
        $merged['qr_verify_url'] = $verifyUrl;

        return $merged;
    }

    public function buildAutoReplacements(array $replacements): array
    {
        return array_merge([
            'date_du_jour' => 'date_du_jour',
            'date_today' => 'date_today',
            'date' => 'date',
            'aujourd_hui' => 'aujourd_hui',
            'date_generation' => 'date_generation',
            'nom_responsable' => 'nom_responsable',
            'responsable' => 'responsable',
            'signataire' => 'signataire',
            'nom_signataire' => 'nom_signataire',
            'document_number' => 'document_number',
            'qr_verify_url' => 'qr_verify_url',
        ], $replacements);
    }

    public function slugify(string $text): string
    {
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        $text = ($ascii !== false && $ascii !== '') ? $ascii : $text;

        $text = strtolower($text);
        $text = str_replace("'", '_', $text);
        $text = preg_replace('/[^a-z0-9]+/', '_', $text);
        $text = trim((string) $text, '_');

        return $text !== '' ? $text : 'var';
    }
}
