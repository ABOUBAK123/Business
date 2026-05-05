<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Signature;
use App\Models\SignatureProviderConfig;
use App\Models\SignatureRequest;
use App\Models\User;
use App\Models\UserDirectionAssignment;
use App\Models\Workflow;
use App\Models\WorkflowExecution;
use App\Models\WorkflowStep;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SignatureController extends Controller
{
    private ?string $lastPlatformError = null;

    public function index()
    {
        $userId = Auth::id();

        // Demandes de signature en attente pour moi
        $pendingRequests = SignatureRequest::with(['document', 'requester'])
            ->where('requested_to', $userId)
            ->where('status', 'pending')
            ->latest('created_at')->get();

        // Mes signatures effectuées
        $mySignatures = Signature::with('document')
            ->where('signer_id', $userId)
            ->latest('created_at')->paginate(10);

        // Documents disponibles (pour le formulaire de demande/signature)
        $documents = Document::whereNull('deleted_at')
            ->where(function ($q) use ($userId) {
                $q->where('owner_id', $userId)->orWhere('created_by', $userId);
            })
            ->orderBy('title')->get(['id', 'title', 'status', 'mime_type']);

        // Tous les utilisateurs (pour choisir un destinataire)
        $users = User::where('id', '!=', $userId)->orderBy('name')->get(['id', 'name', 'email']);

        // Mes workflows avec étapes et exécutions
        $myWorkflows = Workflow::with(['steps.assignee', 'executions.document', 'creator'])
            ->where('created_by', $userId)
            ->latest()->get();

        // Boîte de réception workflow : exécutions où c'est mon tour
        $workflowInbox = $this->buildWorkflowInbox($userId);

        return view('signatures.index', compact(
            'pendingRequests', 'mySignatures', 'documents', 'users', 'myWorkflows', 'workflowInbox'
        ));
    }

    /**
     * Construire la boîte de réception des actions workflow pour l'utilisateur courant.
     */
    private function buildWorkflowInbox(string $userId): array
    {
        $rows = [];

        $notifiedWorkflowIds = Notification::where('recipient_id', $userId)
            ->whereNotNull('workflow_id')
            ->pluck('workflow_id');

        $workflows = Workflow::with(['steps.assignee', 'executions.document', 'creator'])
            ->where('created_by', $userId)
            ->orWhereHas('steps', fn($q) => $q->where('assignee_id', $userId))
            ->orWhereIn('id', $notifiedWorkflowIds)
            ->get();

        foreach ($workflows as $wf) {
            $steps      = $wf->steps->sortBy('order')->values();
            $executions = $wf->executions;

            // Regrouper par étape courante + type d'action
            $grouped = [];
            foreach ($executions as $exec) {
                $currentStep = (int) ($exec->current_step ?? 1);
                $currentStepObj = $steps->firstWhere('order', $currentStep);
                $totalSteps = max($steps->count(), 1);

                $desc = strtolower($currentStepObj->description ?? $currentStepObj->name ?? '');
                $isSignature = str_contains($desc, 'signature') || ($currentStepObj->requires_signature ?? false);
                $actionType = $isSignature ? 'signature' : 'validation';

                $assigneeId = $currentStepObj->assignee_id ?? null;
                $isMyTurn   = !$assigneeId || $assigneeId === $userId;

                $status = $exec->status ?? 'pending';
                $completed = $status === 'completed' ? $totalSteps : max(0, min($currentStep - 1, $totalSteps));
                $progress  = $totalSteps > 0 ? round(($completed / $totalSteps) * 100) : 0;
                if ($status === 'completed') $progress = 100;

                $nextStepObj  = $steps->firstWhere('order', $currentStep + 1);
                $nextActorLabel = match(true) {
                    $status === 'completed' => 'Workflow terminé',
                    $status === 'rejected'  => 'Workflow rejeté',
                    $nextStepObj !== null   => $nextStepObj->assignee?->name ?? $nextStepObj->assignee?->email ?? 'Fin de workflow',
                    default                 => 'Fin de workflow',
                };

                $key = $wf->id . '::' . $actionType . '::' . $currentStep;

                if (!isset($grouped[$key])) {
                    $grouped[$key] = [
                        'workflowId'    => $wf->id,
                        'workflowName'  => $wf->name,
                        'creatorLabel'  => $wf->creator?->name ?? $wf->creator?->email ?? 'Inconnu',
                        'actionType'    => $actionType,
                        'items'         => [],
                    ];
                }

                $grouped[$key]['items'][] = [
                    'executionId'   => $exec->id,
                    'documentTitle' => $exec->document?->title ?? 'Document',
                    'status'        => $status,
                    'progress'      => $progress,
                    'nextActorLabel'=> $nextActorLabel,
                    'isMyTurn'      => $isMyTurn,
                ];
            }

            foreach ($grouped as $row) {
                $statuses  = array_column($row['items'], 'status');
                $rowStatus = in_array('in_progress', $statuses) ? 'in_progress'
                    : (in_array('pending', $statuses) ? 'pending'
                    : (in_array('rejected', $statuses) ? 'rejected' : 'completed'));

                $statusLabel = match($rowStatus) {
                    'completed'  => 'Terminé',
                    'rejected'   => 'Rejeté',
                    'in_progress'=> 'En cours',
                    default      => 'Démarré',
                };

                $avgProgress = count($row['items']) > 0
                    ? (int) round(array_sum(array_column($row['items'], 'progress')) / count($row['items']))
                    : 0;

                $inProgress  = collect($row['items'])->firstWhere('status', 'in_progress');
                $representative = $inProgress ?? $row['items'][0] ?? [];
                $actionableIds  = collect($row['items'])
                    ->filter(fn($i) => $i['status'] === 'in_progress' && $i['isMyTurn'])
                    ->pluck('executionId')->all();

                $rows[] = [
                    'workflowId'         => $row['workflowId'],
                    'workflowName'       => $row['workflowName'],
                    'creatorLabel'       => $row['creatorLabel'],
                    'actionType'         => $row['actionType'],
                    'documentTitle'      => count($row['items']) === 1 ? ($representative['documentTitle'] ?? 'Document') : count($row['items']) . ' documents',
                    'status'             => $rowStatus,
                    'statusLabel'        => $statusLabel,
                    'progress'           => $avgProgress,
                    'nextActorLabel'     => $rowStatus === 'completed' ? 'Workflow terminé' : ($representative['nextActorLabel'] ?? 'Fin de workflow'),
                    'isMyTurn'           => count($actionableIds) > 0,
                    'actionableIds'      => $actionableIds,
                ];
            }
        }

        // Trier : mon tour en premier, puis en cours, puis par nom
        usort($rows, function ($a, $b) {
            if ($a['isMyTurn'] !== $b['isMyTurn']) return $a['isMyTurn'] ? -1 : 1;
            if ($a['status'] !== $b['status']) {
                $ap = $a['status'] === 'in_progress';
                $bp = $b['status'] === 'in_progress';
                if ($ap !== $bp) return $ap ? -1 : 1;
            }
            return strcmp($a['workflowName'], $b['workflowName']);
        });

        return $rows;
    }

    public function store(Request $request)
    {
        $request->validate([
            'document_id' => 'required|exists:documents,id',
            'signature'   => 'required|string',
            'reason'      => 'nullable|string|max:500',
        ]);

        Signature::create([
            'id'          => Str::uuid(),
            'document_id' => $request->document_id,
            'signer_id'   => Auth::id(),
            'signature'   => $request->signature,
            'reason'      => $request->reason,
            'status'      => 'valid',
            'is_valid'    => true,
        ]);

        Document::find($request->document_id)?->update(['status' => 'signed', 'signed_at' => now()]);

        SignatureRequest::where('document_id', $request->document_id)
            ->where('requested_to', Auth::id())
            ->where('status', 'pending')
            ->update(['status' => 'signed', 'responded_at' => now()]);

        return redirect()->route('signatures.index')->with('success', 'Document signé avec succès.');
    }

    public function show(Signature $signature)
    {
        return view('signatures.show', compact('signature'));
    }

    public function request(Request $request)
    {
        $request->validate([
            'document_id'  => 'required|exists:documents,id',
            'requested_to' => 'required|exists:users,id',
            'message'      => 'nullable|string',
            'expiry_date'  => 'nullable|date|after:today',
        ]);

        SignatureRequest::create([
            'id'           => Str::uuid(),
            'document_id'  => $request->document_id,
            'requested_by' => Auth::id(),
            'requested_to' => $request->requested_to,
            'message'      => $request->message,
            'status'       => 'pending',
            'expiry_date'  => $request->expiry_date,
        ]);

        return back()->with('success', 'Demande de signature envoyée.');
    }

    public function decline(SignatureRequest $signatureRequest)
    {
        abort_if($signatureRequest->requested_to !== Auth::id(), 403);
        $signatureRequest->update(['status' => 'declined', 'responded_at' => now()]);
        return back()->with('success', 'Demande refusée.');
    }

    /**
     * Afficher la page de positionnement de la zone de signature
     */
    public function position(SignatureRequest $signatureRequest)
    {
        abort_if($signatureRequest->requested_to !== Auth::id(), 403);
        abort_if($signatureRequest->status !== 'pending', 403);

        $signatureRequest->load('document', 'requester');

        // Récupérer les zones de signature pré-définies sur le template du document
        $templateZones = [];
        $doc = $signatureRequest->document;
        if ($doc && $doc->template_id) {
            $tpl = \App\Models\DocumentTemplate::find($doc->template_id);
            if ($tpl && $tpl->signature_zones) {
                $decoded = json_decode($tpl->signature_zones, true);
                if (is_array($decoded)) {
                    $templateZones = $decoded;
                }
            }
        }

        return view('signatures.position', compact('signatureRequest', 'templateZones'));
    }

    /**
     * Sauvegarder la zone de signature et enregistrer la signature
     */
    public function sign(Request $request, SignatureRequest $signatureRequest)
    {
        abort_if($signatureRequest->requested_to !== Auth::id(), 403);
        abort_if($signatureRequest->status !== 'pending', 403);

        $request->validate([
            'signature'   => 'required|string',
            'reason'      => 'nullable|string|max:500',
            'zone_page'   => 'required|integer|min:1',
            'zone_x'      => 'required|numeric|min:0|max:100',
            'zone_y'      => 'required|numeric|min:0|max:100',
            'zone_width'  => 'required|numeric|min:1|max:100',
            'zone_height' => 'required|numeric|min:1|max:100',
            'zone_label'  => 'nullable|string|max:255',
        ]);

        // Sauvegarder la zone dans la demande
        $signatureRequest->update([
            'zone_page'   => $request->zone_page,
            'zone_x'      => $request->zone_x,
            'zone_y'      => $request->zone_y,
            'zone_width'  => $request->zone_width,
            'zone_height' => $request->zone_height,
            'zone_label'  => $request->zone_label,
            'status'      => 'signed',
            'responded_at'=> now(),
        ]);

        // Créer la signature
        Signature::create([
            'id'          => Str::uuid(),
            'document_id' => $signatureRequest->document_id,
            'signer_id'   => Auth::id(),
            'signature'   => $request->signature,
            'reason'      => $request->reason,
            'status'      => 'valid',
            'is_valid'    => true,
            'signed_at'   => now(),
        ]);

        // Mettre à jour le document
        Document::find($signatureRequest->document_id)?->update([
            'status'    => 'signed',
            'signed_at' => now(),
        ]);

        return redirect()->route('signatures.index')
            ->with('success', 'Document signé avec succès. La signature a été positionnée à la page ' . $request->zone_page . '.');
    }

    public function create() { return view('signatures.create'); }

    /**
     * Action workflow depuis la boîte de réception (signer ou valider une exécution).
     */
    public function workflowAction(Request $request)
    {
        $request->validate([
            'execution_ids'   => 'required|array',
            'execution_ids.*' => 'required|string',
            'action_type'     => 'required|in:signature,validation',
        ]);

        $userId = Auth::id();
        $actionType = $request->action_type;
        $successCount = 0;

        foreach ($request->execution_ids as $executionId) {
            $execution = WorkflowExecution::find($executionId);
            if (!$execution || $execution->status !== 'in_progress') continue;

            $wf    = $execution->workflow()->with('steps')->first();
            $steps = $wf?->steps?->sortBy('order') ?? collect();
            $currentStep = (int) ($execution->current_step ?? 1);
            $currentStepObj = $steps->firstWhere('order', $currentStep);
            $assigneeId = $currentStepObj?->assignee_id;

            // Vérifier que c'est bien le tour de l'utilisateur
            if ($assigneeId && $assigneeId !== $userId) continue;

            $totalSteps = $steps->count();
            $nextStep   = $currentStep + 1;

            if ($nextStep > $totalSteps) {
                // Dernière étape : terminer le workflow
                $execution->update(['status' => 'completed', 'current_step' => $nextStep, 'completed_at' => now()]);
                if ($execution->document_id) {
                    Document::find($execution->document_id)?->update(['status' => 'signed', 'signed_at' => now()]);
                    // Créer une signature automatique
                    Signature::create([
                        'id'          => Str::uuid(),
                        'document_id' => $execution->document_id,
                        'signer_id'   => $userId,
                        'signature'   => hash('sha256', $userId . $execution->document_id . now()),
                        'status'      => 'valid',
                        'is_valid'    => true,
                        'signed_at'   => now(),
                        'reason'      => 'Signature via workflow ' . ($wf?->name ?? ''),
                    ]);
                }
            } else {
                $execution->update(['current_step' => $nextStep]);
            }

            $successCount++;
        }

        $msg = $successCount > 0
            ? ($actionType === 'signature'
                ? "Signature effectuée sur {$successCount} document(s)."
                : "Validation effectuée sur {$successCount} document(s).")
            : 'Aucune action effectuée (vérifiez les droits ou le statut des exécutions).';

        return back()->with($successCount > 0 ? 'success' : 'error', $msg);
    }

    /**
     * Créer un nouveau workflow de signature depuis le modal.
     */
    public function workflowCreate(Request $request)
    {
        $request->validate([
            'name'                    => 'required|string|max:255',
            'description'             => 'nullable|string',
            'validation_approvers'    => 'nullable|array',
            'validation_approvers.*'  => 'nullable|exists:users,id',
            'signature_signers'       => 'nullable|array',
            'signature_signers.*'     => 'nullable|exists:users,id',
            'docs_to_sign'            => 'nullable|array',
            'docs_to_sign.*'          => 'nullable|exists:documents,id',
        ]);

        $userId = Auth::id();

        // Filtrer les valeurs vides
        $approvers = array_filter($request->input('validation_approvers', []), fn($v) => !empty($v));
        $signers   = array_filter($request->input('signature_signers', []),   fn($v) => !empty($v));
        $docsToSign = array_filter($request->input('docs_to_sign', []),       fn($v) => !empty($v));

        if (empty($approvers) && empty($signers)) {
            return back()->with('error', 'Ajoutez au moins une étape de validation ou de signature.')->withInput();
        }

        $wf = Workflow::create([
            'id'          => Str::uuid(),
            'name'        => $request->name,
            'description' => $request->description,
            'status'      => 'active',
            'created_by'  => $userId,
            'docs_to_sign'=> array_values($docsToSign),
        ]);

        $order = 1;
        foreach (array_values($approvers) as $approverId) {
            $wf->steps()->create([
                'id'          => Str::uuid(),
                'name'        => 'Validation ' . $order,
                'type'        => 'validation',
                'assignee_id' => $approverId,
                'order'       => $order,
                'description' => 'Étape de validation',
                'requires_signature' => false,
            ]);
            $order++;
        }
        foreach (array_values($signers) as $signerId) {
            $wf->steps()->create([
                'id'          => Str::uuid(),
                'name'        => 'Signature ' . $order,
                'type'        => 'signature',
                'assignee_id' => $signerId,
                'order'       => $order,
                'description' => 'Étape de signature',
                'requires_signature' => true,
            ]);
            $order++;
        }

        // Démarrer les exécutions pour chaque document
        foreach (array_values($docsToSign) as $docId) {
            WorkflowExecution::create([
                'id'          => Str::uuid(),
                'workflow_id' => $wf->id,
                'document_id' => $docId,
                'current_step'=> 1,
                'status'      => 'in_progress',
                'started_at'  => now(),
            ]);
        }

        return back()->with('success', "Workflow \"{$wf->name}\" créé et démarré avec succès.");
    }
    public function edit(string $id) {}
    public function update(Request $request, string $id) {}
    public function destroy(string $id) {}

    /**
     * Afficher la page d'upload & signer (fichier depuis l'ordinateur)
     */
    public function showUpload()
    {
        return view('signatures.upload');
    }

    /**
     * Traiter l'upload du PDF + zone + signature
     */
    public function handleUpload(Request $request)
    {
        $request->validate([
            'pdf_file'    => 'required|file|mimes:pdf|max:20480',
            'signature'   => 'required|string',
            'reason'      => 'nullable|string|max:500',
            'zone_page'   => 'required|integer|min:1',
            'zone_x'      => 'required|numeric|min:0|max:100',
            'zone_y'      => 'required|numeric|min:0|max:100',
            'zone_width'  => 'required|numeric|min:1|max:100',
            'zone_height' => 'required|numeric|min:1|max:100',
            'zone_label'  => 'nullable|string|max:255',
            'doc_title'   => 'nullable|string|max:255',
        ]);

        // Stocker le fichier
        $file      = $request->file('pdf_file');
        $filePath  = $file->store('uploads/signatures', 'public');
        $title     = $request->doc_title ?: pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

        // Créer le document
        $document = Document::create([
            'id'         => Str::uuid(),
            'title'      => $title,
            'file_path'  => 'storage/' . $filePath,
            'file_size'  => $file->getSize(),
            'mime_type'  => 'application/pdf',
            'status'     => 'signed',
            'owner_id'   => Auth::id(),
            'created_by' => Auth::id(),
            'signed_at'  => now(),
        ]);

        // Créer la demande de signature (pour enregistrer la zone)
        $sigRequest = SignatureRequest::create([
            'id'           => Str::uuid(),
            'document_id'  => $document->id,
            'requested_by' => Auth::id(),
            'requested_to' => Auth::id(),
            'status'       => 'signed',
            'responded_at' => now(),
            'zone_page'    => $request->zone_page,
            'zone_x'       => $request->zone_x,
            'zone_y'       => $request->zone_y,
            'zone_width'   => $request->zone_width,
            'zone_height'  => $request->zone_height,
            'zone_label'   => $request->zone_label,
        ]);

        // Créer la signature
        Signature::create([
            'id'          => Str::uuid(),
            'document_id' => $document->id,
            'signer_id'   => Auth::id(),
            'signature'   => $request->signature,
            'reason'      => $request->reason,
            'status'      => 'valid',
            'is_valid'    => true,
            'signed_at'   => now(),
        ]);

        return redirect()->route('signatures.index')
            ->with('success', 'Document "' . $title . '" signé et enregistré avec succès.');
    }

    // ────────────────────────────────────────────────────────────────────────
    // API Signature externe (Goodflag / SunnyStamp)
    // ────────────────────────────────────────────────────────────────────────

    /**
     * Résoudre la configuration API de signature pour l'utilisateur connecté.
     */
    private function resolveSignatureConfig(): ?SignatureProviderConfig
    {
        $user = Auth::user();
        if (!$user) return null;
        $adminId = $user->profile?->administration_id ?? null;
        if (!$adminId) return null;

        return SignatureProviderConfig::where('administration_id', $adminId)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Résoudre le user_id de la plateforme pour un email donné.
     * Cherche l'utilisateur sur la plateforme via GET /api/users?email={email}.
     * Résultat mis en cache 1 h pour éviter des appels répétés.
     */
    public static function resolvePlatformUserIdByEmail(SignatureProviderConfig $cfg, string $email): ?string
    {
        $email = strtolower(trim($email));
        $cacheKey = 'sunnystamp_uid_' . md5($cfg->endpoint . '|' . $email);
        $cached = \Illuminate\Support\Facades\Cache::get($cacheKey);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $resolved = (function () use ($cfg, $email) {
            try {
                $resp = Http::withToken($cfg->api_key)
                    ->timeout(10)
                    ->when(!(bool) $cfg->verify_ssl, fn($h) => $h->withoutVerifying())
                    ->get($cfg->endpoint . '/api/users', ['email' => $email]);

                if ($resp->successful()) {
                    $body = $resp->json();

                    $extractUserId = function (array $user) use ($email): ?string {
                        $userEmail = strtolower(trim((string) ($user['email'] ?? $user['emailAddress'] ?? $user['mail'] ?? '')));
                        $userId = (string) ($user['id'] ?? $user['userId'] ?? $user['uuid'] ?? '');
                        if ($userId === '') {
                            return null;
                        }
                        if ($userEmail !== '' && $userEmail === $email) {
                            return $userId;
                        }
                        return null;
                    };

                    $scanUsers = function ($collection) use ($extractUserId): ?string {
                        if (!is_array($collection)) {
                            return null;
                        }

                        foreach ($collection as $row) {
                            if (is_array($row)) {
                                $id = $extractUserId($row);
                                if ($id) {
                                    return $id;
                                }
                            }
                        }

                        // Fallback: premier élément avec id quand l'API ne renvoie pas l'email.
                        foreach ($collection as $row) {
                            if (is_array($row) && !empty($row['id'])) {
                                return (string) $row['id'];
                            }
                        }

                        return null;
                    };

                    // Réponse tableau brut
                    $id = $scanUsers($body);
                    if ($id) {
                        return $id;
                    }

                    // Réponse objet (direct/paginé)
                    if (is_array($body)) {
                        if (!empty($body['id'])) {
                            return (string) $body['id'];
                        }

                        foreach (['data', 'items', 'results', 'users', 'content'] as $bucket) {
                            $id = $scanUsers($body[$bucket] ?? null);
                            if ($id) {
                                return $id;
                            }
                        }

                        // Certaines API encapsulent sous _embedded.users
                        $id = $scanUsers($body['_embedded']['users'] ?? null);
                        if ($id) {
                            return $id;
                        }
                    }
                }

                Log::warning('SunnyStamp: utilisateur non trouvé par email', [
                    'email'  => $email,
                    'status' => $resp->status(),
                    'body'   => $resp->body(),
                ]);
            } catch (\Throwable $e) {
                Log::warning('SunnyStamp: erreur recherche utilisateur par email', [
                    'email' => $email,
                    'error' => $e->getMessage(),
                ]);
            }
            return null;
        })();

        // Ne met en cache que les IDs valides pour éviter un faux négatif persistant.
        if (is_string($resolved) && $resolved !== '') {
            \Illuminate\Support\Facades\Cache::put($cacheKey, $resolved, 3600);
        }

        return $resolved;
    }

    /**
     * Résoudre l'utilisateur owner du token API via /api/users/me.
     * Utilisé en fallback quand la recherche par email n'est pas disponible.
     */
    public static function resolvePlatformOwnerUserId(SignatureProviderConfig $cfg): ?string
    {
        try {
            $resp = Http::withToken($cfg->api_key)
                ->timeout(10)
                ->when(!(bool) $cfg->verify_ssl, fn($h) => $h->withoutVerifying())
                ->get($cfg->endpoint . '/api/users/me');

            if (!$resp->successful()) {
                Log::warning('SunnyStamp: échec /api/users/me', [
                    'status' => $resp->status(),
                    'body' => $resp->body(),
                ]);
                return null;
            }

            $body = $resp->json();
            if (is_array($body)) {
                if (!empty($body['id'])) {
                    return (string) $body['id'];
                }
                if (!empty($body['data']['id'])) {
                    return (string) $body['data']['id'];
                }
                if (!empty($body['user']['id'])) {
                    return (string) $body['user']['id'];
                }
            }

            Log::warning('SunnyStamp: /api/users/me sans id exploitable', [
                'body' => $resp->body(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('SunnyStamp: exception /api/users/me', [
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Crée le workflow sur la plateforme, upload le document, le démarre
     * et génère le lien d'invitation pour le signataire/approbateur.
     */
    private function buildPlatformInviteUrl(
        SignatureProviderConfig $cfg,
        string $ownerUserId,
        string $actionType,
        Document $document,
        User $signer
    ): ?string {
        $this->lastPlatformError = null;

        $endpoint  = $cfg->endpoint;
        $token     = $cfg->api_key;
        $timeout   = max(10, (int) round($cfg->timeout_ms / 1000));
        $verifySSL = (bool) $cfg->verify_ssl;

        $consentPageId = $actionType === 'signature'
            ? ($cfg->consent_page_id ?? '')
            : ($cfg->consent_page_id_approval ?: $cfg->consent_page_id ?? '');
        $sigProfileId = $cfg->signature_profile_id ?? '';

        $client = Http::withToken($token)
            ->timeout($timeout)
            ->when(!$verifySSL, fn($h) => $h->withoutVerifying());

        // 1. Créer le workflow
        $stepType  = $actionType === 'signature' ? 'signature' : 'approval';
        $recipient = ['email' => $signer->email, 'firstName' => $signer->name, 'maxInvites' => 1];
        if (!empty($consentPageId)) {
            $recipient['consentPageId'] = $consentPageId;
        }

        $workflowPayload = [
            'name'           => 'e-Parapheur — ' . $document->title,
            'steps'          => [[
                'stepType'           => $stepType,
                'recipients'         => [$recipient],
                'requiredRecipients' => 1,
                'validityPeriod'     => 86400000,
                'invitePeriod'       => 86400000,
                'maxInvites'         => 1,
                'sendDownloadLink'   => false,
            ]],
            'workflowMode'   => 'FULL',
            'notifiedEvents' => ['workflowFinished', 'recipientFinished', 'recipientRefused'],
        ];

        $wflResp = $client->post("{$endpoint}/api/users/{$ownerUserId}/workflows", $workflowPayload);

        if (!$wflResp->successful()) {
            // Fallback payload: certaines versions API refusent des champs avancés.
            $fallbackPayload = [
                'name' => 'e-Parapheur — ' . $document->title,
                'steps' => [[
                    'stepType' => $stepType,
                    'recipients' => [[
                        'email' => $signer->email,
                        'firstName' => $signer->name,
                    ]],
                    'requiredRecipients' => 1,
                ]],
            ];

            $fallbackResp = $client->post("{$endpoint}/api/users/{$ownerUserId}/workflows", $fallbackPayload);
            if ($fallbackResp->successful()) {
                $wflResp = $fallbackResp;
            }
        }

        if (!$wflResp->successful()) {
            $this->lastPlatformError = 'create_workflow: HTTP ' . $wflResp->status() . ' - ' . Str::limit((string) $wflResp->body(), 500, '...');
            Log::error('SunnyStamp: échec création workflow', [
                'status' => $wflResp->status(), 'body' => $wflResp->body(),
            ]);
            return null;
        }
        $workflowId = $wflResp->json('id');

        // 2. Uploader le document PDF
        $filePath = $document->file_path ?? '';
        $absolutePath = str_starts_with($filePath, 'storage/')
            ? public_path($filePath)
            : Storage::disk('public')->path($filePath);

        if (!file_exists($absolutePath)) {
            $this->lastPlatformError = 'upload_document: fichier introuvable (' . $absolutePath . ')';
            Log::error('SunnyStamp: fichier introuvable', ['path' => $absolutePath]);
            return null;
        }

        $uploadQuery = ['createDocuments' => 'true'];
        if (!empty($sigProfileId)) {
            $uploadQuery['signatureProfileId'] = $sigProfileId;
        }

        $uploadResp = $client
            ->attach('document', file_get_contents($absolutePath), basename($absolutePath), ['Content-Type' => 'application/pdf'])
            ->post("{$endpoint}/api/workflows/{$workflowId}/parts?" . http_build_query($uploadQuery));

        if (!$uploadResp->successful()) {
            $this->lastPlatformError = 'upload_document: HTTP ' . $uploadResp->status() . ' - ' . Str::limit((string) $uploadResp->body(), 500, '...');
            Log::error('SunnyStamp: échec upload document', [
                'status' => $uploadResp->status(), 'body' => $uploadResp->body(),
            ]);
            return null;
        }

        // 3. Démarrer le workflow
        $startResp = $client->patch("{$endpoint}/api/workflows/{$workflowId}", [
            'workflowStatus' => 'started',
        ]);
        if (!$startResp->successful()) {
            $this->lastPlatformError = 'start_workflow: HTTP ' . $startResp->status() . ' - ' . Str::limit((string) $startResp->body(), 500, '...');
            Log::error('SunnyStamp: échec démarrage workflow', [
                'status' => $startResp->status(), 'body' => $startResp->body(),
            ]);
            return null;
        }

        // 4. Créer le lien d'invitation
        $inviteResp = $client->post("{$endpoint}/api/workflows/{$workflowId}/invite", [
            'recipientEmail' => $signer->email,
        ]);
        if ($inviteResp->successful()) {
            return $inviteResp->json('inviteUrl');
        }

        Log::warning('SunnyStamp: invite non créé, fallback portail', [
            'status' => $inviteResp->status(), 'body' => $inviteResp->body(),
        ]);
        $this->lastPlatformError = 'invite: HTTP ' . $inviteResp->status() . ' - ' . Str::limit((string) $inviteResp->body(), 500, '...');
        return $endpoint . '/portal';
    }

    /**
     * AJAX — Obtenir le lien d'invitation vers la plateforme pour Signer / Valider.
     * POST /signatures/get-invite-url
     */
    public function getSignatureInviteUrl(Request $request): JsonResponse
    {
        $request->validate([
            'execution_id' => 'required|string',
            'action_type'  => 'required|in:signature,validation',
        ]);

        $cfg = $this->resolveSignatureConfig();
        if (!$cfg) {
            Log::warning('SunnyStamp: aucune configuration API active pour utilisateur', [
                'user_id' => Auth::id(),
                'email' => Auth::user()?->email,
                'admin_id' => Auth::user()?->profile?->administration_id,
            ]);
            return response()->json([
                'ok'      => false,
                'message' => 'Aucune configuration API Signature active pour votre administration.',
            ], 422);
        }

        $currentUser   = Auth::user();
        $platformUserId = self::resolvePlatformUserIdByEmail($cfg, $currentUser->email);
        if (!$platformUserId) {
            // Fallback: certaines plateformes n'autorisent pas la recherche utilisateur par email.
            // On utilise alors l'utilisateur owner du token API pour créer le workflow.
            $platformUserId = self::resolvePlatformOwnerUserId($cfg);
        }
        if (!$platformUserId) {
            Log::warning('SunnyStamp: compte plateforme introuvable pour utilisateur', [
                'user_id' => $currentUser->id,
                'email' => $currentUser->email,
                'admin_id' => $currentUser->profile?->administration_id,
                'endpoint' => $cfg->endpoint,
            ]);
            return response()->json([
                'ok'      => false,
                'message' => 'Impossible de trouver votre compte sur la plateforme de signature. Vérifiez que l\'adresse e-mail ' . $currentUser->email . ' est bien enregistrée sur la plateforme.',
            ], 422);
        }

        $userId    = Auth::id();
        $execution = WorkflowExecution::find($request->input('execution_id'));
        if (!$execution) {
            return response()->json(['ok' => false, 'message' => 'Exécution introuvable.'], 404);
        }

        // Vérifier que c'est le tour de l'utilisateur
        $wf     = $execution->workflow()->with('steps')->first();
        $steps  = $wf?->steps?->sortBy('order') ?? collect();
        $stepObj = $steps->firstWhere('order', (int) ($execution->current_step ?? 1));
        if ($stepObj?->assignee_id && $stepObj->assignee_id !== $userId) {
            return response()->json(['ok' => false, 'message' => 'Ce n\'est pas votre tour.'], 403);
        }

        $document = $execution->document;
        if (!$document) {
            return response()->json(['ok' => false, 'message' => 'Document introuvable.'], 404);
        }

        $inviteUrl = $this->buildPlatformInviteUrl(
            $cfg,
            $platformUserId,
            $request->input('action_type'),
            $document,
            $currentUser
        );

        if (!$inviteUrl) {
            $msg = 'Erreur lors de la création du workflow sur la plateforme. Consultez les logs.';
            if (!empty($this->lastPlatformError)) {
                $msg .= ' Détail: ' . $this->lastPlatformError;
            }
            return response()->json([
                'ok'      => false,
                'message' => $msg,
            ], 500);
        }

        return response()->json(['ok' => true, 'url' => $inviteUrl]);
    }
}
