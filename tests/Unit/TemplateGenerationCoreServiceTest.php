<?php

namespace Tests\Unit;

use App\Models\AppSetting;
use App\Models\DocumentTemplate;
use App\Models\IssuingAdministration;
use App\Models\TemplateVariable;
use App\Models\User;
use App\Services\Templates\TemplateGenerationCoreService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TemplateGenerationCoreServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_assert_required_values_throws_when_missing_required_fields(): void
    {
        $service = new TemplateGenerationCoreService();

        $template = new DocumentTemplate([
            'name' => 'Attestation',
            'file_name' => 'attestation.docx',
            'file_type' => 'docx',
        ]);

        $template->setRelation('variables', collect([
            new TemplateVariable([
                'key' => 'nom_demandeur',
                'label' => 'Nom du demandeur',
                'required' => true,
                'field_type' => 'text',
            ]),
            new TemplateVariable([
                'key' => 'objet',
                'label' => 'Objet',
                'required' => false,
                'field_type' => 'text',
            ]),
        ]));

        $this->expectException(ValidationException::class);
        $service->assertRequiredValues($template, ['nom_demandeur' => '']);
    }

    public function test_extract_content_variables_supports_curly_and_legacy_syntax(): void
    {
        $service = new TemplateGenerationCoreService();

        $vars = $service->extractContentVariables('Bonjour {{Nom complet}} et [Date du jour].');

        $this->assertArrayHasKey('nom_complet', $vars);
        $this->assertSame('Nom complet', $vars['nom_complet']);
        $this->assertArrayHasKey('date_du_jour', $vars);
        $this->assertSame('Date du jour', $vars['date_du_jour']);
    }

    public function test_build_auto_values_preserves_system_fields(): void
    {
        $service = new TemplateGenerationCoreService();

        $values = $service->buildAutoValues(
            [
                'nom' => 'ABDOU',
                'document_number' => 'HACKED',
                'qr_verify_url' => 'https://evil.local',
            ],
            'ADM - ENT - 00001 - 2026',
            'https://app.exemple.ci/qr-download/token123',
            'Responsable Test'
        );

        $this->assertSame('ABDOU', $values['nom']);
        $this->assertSame('ADM - ENT - 00001 - 2026', $values['document_number']);
        $this->assertSame('https://app.exemple.ci/qr-download/token123', $values['qr_verify_url']);
    }

    public function test_reserve_document_number_increments_counter_by_admin_scope_and_year(): void
    {
        $service = new TemplateGenerationCoreService();

        $user = User::factory()->create();

        $admin = IssuingAdministration::create([
            'id' => (string) Str::uuid(),
            'name' => 'Administration Test',
            'code' => 'MINSANTE',
            'sub_entity_code' => 'CAB',
            'is_active' => true,
        ]);

        \DB::table('user_direction_assignments')->insert([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'direction_scope_type' => 'emitter',
            'direction_scope_id' => $admin->id,
            'sub_entity_code' => 'DPM',
            'direction_label' => 'Direction Pilotage',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $template = DocumentTemplate::create([
            'id' => (string) Str::uuid(),
            'name' => 'Template A',
            'file_name' => 'a.docx',
            'file_type' => 'docx',
            'administration_id' => $admin->id,
        ]);

        $first = $service->reserveDocumentNumber($template, $user->id);
        $second = $service->reserveDocumentNumber($template, $user->id);

        $year = now()->year;
        $this->assertSame('MINSANTE - DPM - 00001 - ' . $year, $first['document_number']);
        $this->assertSame('MINSANTE - DPM - 00002 - ' . $year, $second['document_number']);

        $counterKey = 'doc_counter_' . $admin->id . '_dpm_' . $year;
        $counter = AppSetting::where('key', $counterKey)->value('value');
        $this->assertSame('2', (string) $counter);
    }
}
