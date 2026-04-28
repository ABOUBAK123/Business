<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentTemplate;
use App\Models\DocumentVersion;
use App\Models\IssuingAdministration;
use App\Models\TemplateVariable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class SharedTemplateGenerationFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_generate_returns_422_when_required_variable_is_missing(): void
    {
        $user = User::factory()->create();

        $template = DocumentTemplate::create([
            'id' => (string) Str::uuid(),
            'name' => 'Template Requis',
            'file_name' => 'template-requis.pdf',
            'file_type' => 'pdf',
            'content' => 'Bonjour {{nom_demandeur}}',
            'created_by' => $user->id,
        ]);

        TemplateVariable::create([
            'id' => (string) Str::uuid(),
            'template_id' => $template->id,
            'key' => 'nom_demandeur',
            'label' => 'Nom du demandeur',
            'field_type' => 'text',
            'required' => true,
        ]);

        $response = $this->actingAs($user)
            ->postJson(route('shared-templates.generate', $template), [
                'values' => [
                    'nom_demandeur' => '',
                ],
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['values.nom_demandeur']);
        $this->assertDatabaseCount('documents', 0);
    }

    public function test_generate_creates_document_with_number_qr_and_version(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $admin = IssuingAdministration::create([
            'id' => (string) Str::uuid(),
            'name' => 'Administration Emettrice Test',
            'code' => 'DGS',
            'sub_entity_code' => 'CAB',
            'is_active' => true,
        ]);

        // Sub-entity prioritaire côté numérotation.
        \DB::table('user_direction_assignments')->insert([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'direction_scope_type' => 'emitter',
            'direction_scope_id' => $admin->id,
            'sub_entity_code' => 'DRH',
            'direction_label' => 'Direction RH',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $template = DocumentTemplate::create([
            'id' => (string) Str::uuid(),
            'name' => 'Attestation Presence',
            'file_name' => 'attestation-presence.pdf',
            'file_type' => 'pdf',
            'content' => 'Je soussigné certifie que {{nom}} est présent le {{date_du_jour}}.',
            'administration_id' => $admin->id,
            'created_by' => $user->id,
        ]);

        TemplateVariable::create([
            'id' => (string) Str::uuid(),
            'template_id' => $template->id,
            'key' => 'nom',
            'label' => 'nom',
            'field_type' => 'text',
            'required' => true,
        ]);

        $response = $this->actingAs($user)
            ->postJson(route('shared-templates.generate', $template), [
                'values' => [
                    'nom' => 'KOUASSI Jean',
                    // Ne doit pas écraser la valeur système.
                    'document_number' => 'FAKE-000',
                ],
                'output_format' => 'source',
            ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonStructure([
            'document_id',
            'document_number',
            'qr_token',
            'verify_url',
            'file_path',
        ]);

        $docId = (string) $response->json('document_id');
        $doc = Document::findOrFail($docId);

        $this->assertNotEmpty($doc->document_number);
        $this->assertStringContainsString('DGS - DRH - ', $doc->document_number);
        $this->assertNotEmpty($doc->qr_token);
        $this->assertSame($user->id, $doc->owner_id);
        $this->assertSame($admin->id, $doc->issuing_administration_id);
        $this->assertSame('DRH', $doc->sub_entity_code);

        $this->assertDatabaseHas('document_versions', [
            'document_id' => $doc->id,
            'version' => 1,
        ]);

        $relativePath = ltrim(str_replace('/storage/', '', (string) $doc->file_path), '/');
        Storage::disk('public')->assertExists($relativePath);

        $version = DocumentVersion::where('document_id', $doc->id)->first();
        $this->assertNotNull($version);
        $this->assertSame($doc->file_path, $version->file_path);
    }
}
