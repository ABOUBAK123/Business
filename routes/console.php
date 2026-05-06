<?php

use App\Models\SignatureProviderConfig;
use App\Models\WorkflowExecution;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('signatures:backfill-signed-docs
    {--dry-run : Affiche les exécutions à traiter sans télécharger}
    {--limit=50 : Nombre max d\'exécutions à traiter}
    {--execution-id= : Traiter une exécution précise}', function () {
    $normalizeEndpoint = static function (string $endpoint): string {
        $endpoint = rtrim($endpoint, '/');
        if (str_ends_with($endpoint, '/api')) {
            $endpoint = substr($endpoint, 0, -4);
        }
        return $endpoint;
    };

    $cfg = SignatureProviderConfig::query()
        ->where('is_active', true)
        ->first();

    if (!$cfg) {
        $this->error('Aucune configuration de signature active.');
        return 1;
    }

    $endpoint = $normalizeEndpoint((string) $cfg->endpoint);
    $token = (string) ($cfg->api_key ?? $cfg->api_token ?? '');
    $verifySSL = (bool) ($cfg->verify_ssl ?? true);
    $limit = max(1, (int) $this->option('limit'));
    $executionId = trim((string) $this->option('execution-id'));
    $dryRun = (bool) $this->option('dry-run');

    if ($token === '') {
        $this->error('Token API manquant dans signature_provider_configs.');
        return 1;
    }

    $query = WorkflowExecution::query()
        ->with('document')
        ->where('status', 'completed')
        ->whereNotNull('platform_workflow_id')
        ->whereHas('document', function ($q) {
            $q->whereNull('signed_file_path');
        })
        ->orderByDesc('started_at');

    if ($executionId !== '') {
        $query->where('id', $executionId);
    }

    $executions = $query->limit($limit)->get();
    if ($executions->isEmpty()) {
        $this->info('Aucune exécution à rattraper.');
        return 0;
    }

    $this->info('Exécutions à traiter: ' . $executions->count() . ($dryRun ? ' (dry-run)' : ''));

    $ok = 0;
    $failed = 0;
    $skipped = 0;

    foreach ($executions as $execution) {
        $document = $execution->document;
        if (!$document) {
            $skipped++;
            $this->warn("[SKIP] {$execution->id} document introuvable");
            continue;
        }

        $url = "{$endpoint}/api/workflows/{$execution->platform_workflow_id}/downloadDocuments";

        try {
            $resp = Http::withToken($token)
                ->timeout(90)
                ->when(!$verifySSL, fn($h) => $h->withoutVerifying())
                ->get($url);

            if (!$resp->successful()) {
                $failed++;
                $this->error("[FAIL] {$execution->id} HTTP {$resp->status()}");
                Log::warning('Backfill signed docs: HTTP error', [
                    'execution_id' => $execution->id,
                    'workflow_id' => $execution->platform_workflow_id,
                    'status' => $resp->status(),
                    'body_excerpt' => substr((string) $resp->body(), 0, 300),
                ]);
                continue;
            }

            $pdfContent = (string) $resp->body();
            if ($pdfContent === '' || strlen($pdfContent) < 100) {
                $failed++;
                $this->error("[FAIL] {$execution->id} contenu PDF invalide");
                continue;
            }

            $originalName = pathinfo((string) ($document->file_path ?? 'document.pdf'), PATHINFO_FILENAME);
            $filename = 'signed_' . $originalName . '_backfill_' . now()->format('Ymd_His') . '_' . substr((string) $execution->id, 0, 8) . '.pdf';
            $storagePath = 'signed_documents/' . $filename;

            if ($dryRun) {
                $ok++;
                $this->line("[DRY] {$execution->id} => {$storagePath}");
                continue;
            }

            Storage::disk('public')->makeDirectory('signed_documents');
            Storage::disk('public')->put($storagePath, $pdfContent);

            $document->update([
                'signed_file_path' => $storagePath,
                'status' => 'signed',
                'signed_at' => $document->signed_at ?? now(),
            ]);

            $ok++;
            $this->info("[OK] {$execution->id} => {$storagePath}");
        } catch (\Throwable $e) {
            $failed++;
            $this->error("[FAIL] {$execution->id} exception: {$e->getMessage()}");
            Log::error('Backfill signed docs: exception', [
                'execution_id' => $execution->id,
                'workflow_id' => $execution->platform_workflow_id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    $this->newLine();
    $this->info("Terminé. OK={$ok}, FAIL={$failed}, SKIP={$skipped}");

    return $failed > 0 ? 2 : 0;
})->purpose('Rattrape les PDF signés manquants pour les exécutions completed');
