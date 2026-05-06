<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\PersonnelEmployee;
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
    private ?string $lastPlatformWorkflowId = null;

    /**
     * Convertit le détail technique plateforme en message métier plus clair.
     */
    private static function toBusinessPlatformMessage(?string $detail): string
    {
        $d = (string) ($detail ?? '');
        if ($d === '') {
            return 'Erreur de communication avec la plateforme de signature.';
        }

        if (str_contains($d, 'RecipientPhoneNumberRequired')) {
            return 'Le profil du signataire doit contenir un numéro de téléphone pour cette page de consentement.';
        }

        if (str_starts_with($d, 'create_workflow:')) {
            return 'Impossible de créer le workflow de signature sur la plateforme.';
        }

        if (str_starts_with($d, 'upload_document:')) {
            return 'Le workflow a été créé, mais le document n\'a pas pu être chargé sur la plateforme.';
        }

        if (str_starts_with($d, 'start_workflow:')) {
            return 'Le document a été chargé, mais le démarrage du workflow a échoué sur la plateforme.';
        }

        if (str_contains($d, 'invite: aucun lien exploitable trouvé')) {
            return 'Le workflow est lancé, mais la plateforme ne fournit aucun lien d\'accès signataire (endpoints invite indisponibles ou sans token).';
        }

        if (str_starts_with($d, 'invite:')) {
            return 'Le workflow est lancé, mais la génération du lien d\'invitation a échoué sur la plateforme.';
        }

        return 'Erreur lors de l\'orchestration de la signature sur la plateforme.';
    }

    /**
     * Résout un numéro de téléphone exploitable pour le recipient plateforme.
     */
    private static function resolveRecipientPhone(User $user): ?string
    {
        $employee = PersonnelEmployee::where('user_id', $user->id)->first();

        $candidate = (string) (
            $employee?->phone
            ?? $employee?->secondary_phone
            ?? $user->phone
            ?? $user->phone_number
            ?? $user->mobile
            ?? ''
        );

        $candidate = trim($candidate);
        if ($candidate === '') {
            return null;
        }

        return $candidate;
    }

    /**
     * Formate une erreur API avec status + body + requestId/logId quand disponibles.
     */
    private static function formatApiErrorDetail(\Illuminate\Http\Client\Response $resp): string
    {
        $body = (string) $resp->body();
        $detail = 'HTTP ' . $resp->status() . ' - ' . Str::limit($body, 500, '...');

        $json = $resp->json();
        if (is_array($json)) {
            $requestId = $json['requestId'] ?? $json['request_id'] ?? null;
            $logId = $json['logId'] ?? $json['log_id'] ?? null;

            if (is_string($requestId) && $requestId !== '') {
                $detail .= ' | requestId=' . $requestId;
            }
            if (is_string($logId) && $logId !== '') {
                $detail .= ' | logId=' . $logId;
            }
        }

        return $detail;
    }

    /**
     * Extrait la première URL utile trouvée dans une réponse API (parcours récursif).
     */
    private static function extractFirstUrlFromPayload(mixed $payload): ?string
    {
        if (!is_array($payload)) {
            return null;
        }

        $stack = [$payload];
        while (!empty($stack)) {
            $node = array_pop($stack);
            if (!is_array($node)) {
                continue;
            }

            foreach ($node as $key => $value) {
                if (is_string($value) && preg_match('/^https?:\/\//i', $value)) {
                    $k = strtolower((string) $key);
                    if (str_contains($k, 'invite') || str_contains($k, 'url') || str_contains($k, 'link')) {
                        return $value;
                    }
                }

                if (is_array($value)) {
                    $stack[] = $value;
                }
            }
        }

        return null;
    }

    /**
     * Extrait une URL absolue depuis un texte brut (fallback API non JSON).
     */
    private static function extractFirstUrlFromText(string $text): ?string
    {
        if (preg_match('/https?:\/\/[^\s"\'<>]+/i', $text, $matches) === 1) {
            return $matches[0] ?? null;
        }

        return null;
    }

    /**
     * Extrait l'URL d'invitation depuis la réponse API.
     * Gère : URL complète (inviteUrl/url/link), token JWT (→ construit /invite?token=...),
     * champs imbriqués, tableaux de recipients/steps.
     */
    private function extractInviteUrl(mixed $payload, string $baseEndpoint): ?string
    {
        if (!is_array($payload)) {
            return null;
        }

        $base = rtrim($baseEndpoint, '/');

        // Chercher les champs directs d'abord
        $urlFields  = ['inviteUrl', 'invite_url', 'url', 'link', 'redirectUrl', 'signingUrl', 'accessUrl'];
        $tokenFields = ['token', 'inviteToken', 'invite_token', 'accessToken', 'signingToken'];

        // Parcours récursif (BFS)
        $queue = [$payload];
        while (!empty($queue)) {
            $node = array_shift($queue);
            if (!is_array($node)) {
                continue;
            }

            // URL complète
            foreach ($urlFields as $f) {
                if (isset($node[$f]) && is_string($node[$f]) && str_starts_with($node[$f], 'http')) {
                    return $node[$f];
                }
            }

            // Token → construire URL
            foreach ($tokenFields as $f) {
                if (isset($node[$f]) && is_string($node[$f]) && $node[$f] !== '') {
                    return $base . '/invite?token=' . $node[$f];
                }
            }

            // Descendre dans les clés
            foreach ($node as $key => $child) {
                if (is_array($child)) {
                    $queue[] = $child;
                }
            }
        }

        // Dernier recours : première URL avec invite/url/link dans le nom de clé
        return self::extractFirstUrlFromPayload($payload);
    }

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
                    'documentId'    => $exec->document_id,
                    'documentTitle' => $exec->document?->title ?? 'Document',
                    'signedFilePath'=> $exec->document?->signed_file_path ?? null,
                    'docStatus'     => $exec->document?->status ?? null,
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
                    'documentId'         => $representative['documentId'] ?? null,
                    'signedFilePath'     => $representative['signedFilePath'] ?? null,
                    'firstExecutionId'   => $representative['executionId'] ?? null,
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
    /** Dernière erreur API lors du lookup utilisateur (pour diagnostic). */
    private static ?string $lastLookupApiError = null;

    public static function resolvePlatformUserIdByEmail(SignatureProviderConfig $cfg, string $email): ?string
    {
        $email = strtolower(trim($email));
        $endpoint = self::normalizeEndpoint($cfg->endpoint);
        $cacheKey = 'sunnystamp_uid_' . md5($endpoint . '|' . $email);
        $cached = \Illuminate\Support\Facades\Cache::get($cacheKey);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $resolved = (function () use ($cfg, $email, $endpoint) {
            try {
                $resp = Http::withToken($cfg->api_key)
                    ->timeout(10)
                    ->when(!(bool) $cfg->verify_ssl, fn($h) => $h->withoutVerifying())
                    ->get($endpoint . '/api/users', ['email' => $email]);

                if (!$resp->successful()) {
                    self::$lastLookupApiError = 'GET /api/users HTTP ' . $resp->status() . ' — ' . \Illuminate\Support\Str::limit((string) $resp->body(), 300);
                }

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
                self::$lastLookupApiError = 'Exception: ' . $e->getMessage();
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
            $endpoint = self::normalizeEndpoint($cfg->endpoint);
            $resp = Http::withToken($cfg->api_key)
                ->timeout(10)
                ->when(!(bool) $cfg->verify_ssl, fn($h) => $h->withoutVerifying())
                ->get($endpoint . '/api/users/me');

            if (!$resp->successful()) {
                self::$lastLookupApiError = 'GET /api/users/me HTTP ' . $resp->status() . ' — ' . \Illuminate\Support\Str::limit((string) $resp->body(), 300);
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
    /**
     * Normalise l'endpoint en retirant /api à la fin s'il existe.
     * Permet de gérer à la fois:
     * - https://uvci.artci-sign.ci (ajoute /api/)
     * - https://sigfae.artci-sign.ci/api (retire /api, puis ajoute /api/)
     */
    private static function normalizeEndpoint(string $endpoint): string
    {
        $endpoint = rtrim($endpoint, '/');
        if (str_ends_with($endpoint, '/api')) {
            $endpoint = substr($endpoint, 0, -4); // Retire les 4 derniers chars "/api"
        }
        return $endpoint;
    }

    private function buildPlatformInviteUrl(
        SignatureProviderConfig $cfg,
        string $ownerUserId,
        string $actionType,
        Document $document,
        User $signer
    ): ?string {
        $this->lastPlatformError = null;

        $endpoint  = self::normalizeEndpoint($cfg->endpoint);
        $token     = $cfg->api_key;
        $timeout   = max(10, (int) round($cfg->timeout_ms / 1000));
        $verifySSL = (bool) $cfg->verify_ssl;

        $consentPageId = $actionType === 'signature'
            ? ($cfg->consent_page_id ?? '')
            : ($cfg->consent_page_id_approval ?: $cfg->consent_page_id ?? '');
        $sigProfileId = $cfg->signature_profile_id ?? '';

        $recipientPlatformUserId = self::resolvePlatformUserIdByEmail($cfg, (string) $signer->email);
        $nameParts = preg_split('/\s+/', trim((string) $signer->name)) ?: [];
        $recipientFirstName = (string) ($nameParts[0] ?? $signer->name ?? 'Utilisateur');
        $recipientLastName = trim((string) implode(' ', array_slice($nameParts, 1)));
        $recipientPhone = self::resolveRecipientPhone($signer);

        $client = Http::withToken($token)
            ->timeout($timeout)
            ->when(!$verifySSL, fn($h) => $h->withoutVerifying());

        // 1. Créer le workflow
        $stepType  = $actionType === 'signature' ? 'signature' : 'approval';
        $recipient = [
            'email' => $signer->email,
            'firstName' => $recipientFirstName,
            'lastName' => $recipientLastName,
            'name' => (string) $signer->name,
            'maxInvites' => 1,
        ];
        if (!empty($recipientPhone)) {
            $recipient['phoneNumber'] = $recipientPhone;
        }
        if (!empty($recipientPlatformUserId)) {
            $recipient['id'] = $recipientPlatformUserId;
            $recipient['userId'] = $recipientPlatformUserId;
        }
        if (!empty($consentPageId)) {
            $recipient['consentPageId'] = $consentPageId;
        }

        $platformWebhookUrl = rtrim(config('app.url'), '/') . '/api/signature/platform-webhook';

        $workflowPayload = [
            'name'            => 'e-Parapheur — ' . $document->title,
            'steps'           => [[
                'stepType'           => $stepType,
                'recipients'         => [$recipient],
                'requiredRecipients' => 1,
                'validityPeriod'     => 86400000,
                'invitePeriod'       => 86400000,
                'maxInvites'         => 1,
                'sendDownloadLink'   => false,
            ]],
            'workflowMode'    => 'FULL',
            'notifiedEvents'  => ['workflowFinished', 'recipientFinished', 'recipientRefused', 'workflowStarted', 'recipientStarted'],
            'notificationUrl' => $platformWebhookUrl,
            'webhookUrl'      => $platformWebhookUrl,
            'callbackUrl'     => $platformWebhookUrl,
        ];

        $wflResp = $client->post("{$endpoint}/api/users/{$ownerUserId}/workflows", $workflowPayload);

        if (!$wflResp->successful()) {
            // Fallback payload: certaines versions API refusent des champs avancés.
            $fallbackPayload = [
                'name' => 'e-Parapheur — ' . $document->title,
                'steps' => [[
                    'stepType' => $stepType,
                    'recipients' => [array_filter([
                        'id' => $recipientPlatformUserId,
                        'userId' => $recipientPlatformUserId,
                        'email' => $signer->email,
                        'firstName' => $recipientFirstName,
                        'lastName' => $recipientLastName,
                        'phoneNumber' => $recipientPhone,
                    ], fn($v) => !is_null($v) && $v !== '')],
                    'requiredRecipients' => 1,
                ]],
                'notificationUrl' => $platformWebhookUrl,
                'webhookUrl'      => $platformWebhookUrl,
                'callbackUrl'     => $platformWebhookUrl,
            ];

            $fallbackResp = $client->post("{$endpoint}/api/users/{$ownerUserId}/workflows", $fallbackPayload);
            if ($fallbackResp->successful()) {
                $wflResp = $fallbackResp;
            }
        }

        if (!$wflResp->successful()) {
            $errorCode = is_array($wflResp->json()) ? ($wflResp->json('code') ?? '') : '';
            if ($errorCode === 'RecipientPhoneNumberRequired' && empty($recipientPhone)) {
                $this->lastPlatformError = 'create_workflow: RecipientPhoneNumberRequired - le profil signataire ne contient pas de numero de telephone (champ personnel requis).';
                Log::error('SunnyStamp: création workflow bloquée - phone manquant pour recipient', [
                    'signer_user_id' => $signer->id,
                    'signer_email' => $signer->email,
                ]);
                return null;
            }

            $this->lastPlatformError = 'create_workflow: ' . self::formatApiErrorDetail($wflResp);
            Log::error('SunnyStamp: échec création workflow', [
                'status' => $wflResp->status(), 'body' => $wflResp->body(),
            ]);
            return null;
        }

        // Extraire l'ID du workflow depuis la réponse (clés variées selon le tenant)
        $wflRespJson = $wflResp->json();
        $workflowId = $wflRespJson['id']
            ?? $wflRespJson['workflowId']
            ?? $wflRespJson['workflow_id']
            ?? ($wflRespJson['workflow']['id'] ?? null)
            ?? null;

        if (!is_string($workflowId) || $workflowId === '') {
            $this->lastPlatformError = 'create_workflow: ID du workflow non trouvé dans la réponse API - ' . self::formatApiErrorDetail($wflResp);
            Log::error('SunnyStamp: ID workflow absent', [
                'response' => $wflResp->json(),
            ]);
            return null;
        }

        $this->lastPlatformWorkflowId = $workflowId;

        // 2. Uploader le document PDF
        $filePath = trim((string) ($document->file_path ?? ''));
        $normalizedPublicDiskPath = ltrim($filePath, '/');
        if (str_starts_with($normalizedPublicDiskPath, 'public/')) {
            $normalizedPublicDiskPath = substr($normalizedPublicDiskPath, 7);
        }
        if (str_starts_with($normalizedPublicDiskPath, 'storage/')) {
            $normalizedPublicDiskPath = substr($normalizedPublicDiskPath, 8);
        }

        // Gérer les différents formats historiques de file_path.
        $candidatePaths = array_values(array_unique(array_filter([
            $filePath !== '' ? public_path(ltrim($filePath, '/')) : null,
            $normalizedPublicDiskPath !== '' ? Storage::disk('public')->path($normalizedPublicDiskPath) : null,
            $normalizedPublicDiskPath !== '' ? storage_path('app/public/' . $normalizedPublicDiskPath) : null,
        ])));

        $absolutePath = null;
        foreach ($candidatePaths as $candidatePath) {
            if (is_string($candidatePath) && $candidatePath !== '' && file_exists($candidatePath)) {
                $absolutePath = $candidatePath;
                break;
            }
        }

        if (!$absolutePath) {
            $this->lastPlatformError = 'upload_document: fichier introuvable (' . $filePath . ')';
            Log::error('SunnyStamp: fichier introuvable', [
                'file_path' => $filePath,
                'normalized' => $normalizedPublicDiskPath,
                'candidates' => $candidatePaths,
            ]);
            return null;
        }

        $uploadQuery = ['createDocuments' => 'true'];
        if (!empty($sigProfileId)) {
            $uploadQuery['signatureProfileId'] = $sigProfileId;
        }

        $pdfBytes = file_get_contents($absolutePath);
        if ($pdfBytes === false) {
            $this->lastPlatformError = 'upload_document: impossible de lire le fichier (' . $absolutePath . ')';
            Log::error('SunnyStamp: lecture fichier upload impossible', [
                'absolute_path' => $absolutePath,
            ]);
            return null;
        }

        $fileSize = strlen($pdfBytes);
        $fileHash = base64_encode(hash('sha256', $pdfBytes, true));
        $fileName = basename($absolutePath);

        // Étape 2a: Upload raw binary (Content-Type: application/pdf) → /parts
        // C'est la méthode documentée dans la collection Postman UVCI/ARTCI.
        $uploadUrlWithQuery = "{$endpoint}/api/workflows/{$workflowId}/parts?" . http_build_query($uploadQuery);
        $uploadUrlBase      = "{$endpoint}/api/workflows/{$workflowId}/parts";

        $uploadResp = null;
        foreach ([$uploadUrlWithQuery, $uploadUrlBase] as $uploadUrl) {
            try {
                $candidate = Http::withToken($token)
                    ->timeout($timeout)
                    ->when(!$verifySSL, fn($h) => $h->withoutVerifying())
                    ->withBody($pdfBytes, 'application/pdf')
                    ->withHeaders([
                        'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
                    ])
                    ->post($uploadUrl);

                Log::info('SunnyStamp: tentative upload raw PDF', [
                    'workflow_id' => $workflowId,
                    'url'    => $uploadUrl,
                    'status' => $candidate->status(),
                    'body_excerpt' => substr($candidate->body(), 0, 200),
                ]);

                if ($candidate->successful()) {
                    $uploadResp = $candidate;
                    break;
                }
                $uploadResp = $candidate;
                if (in_array($candidate->status(), [401, 403], true)) {
                    break;
                }
            } catch (\Throwable $e) {
                Log::warning('SunnyStamp: exception upload raw PDF', [
                    'workflow_id' => $workflowId,
                    'url' => $uploadUrl,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Étape 2b: Si upload raw OK → lier le document via POST /documents
        // (requis quand createDocuments=true n'est pas supporté par l'instance).
        if ($uploadResp && $uploadResp->successful()) {
            $partData = $uploadResp->json();
            $partId   = $partData['id'] ?? $partData['partId'] ?? null;

            // Construire le payload /documents avec les métadonnées du fichier.
            $docPayload = [
                'parts' => [[
                    'filename'    => $fileName,
                    'contentType' => 'application/pdf',
                    'size'        => $fileSize,
                    'hash'        => $fileHash,
                ]],
            ];
            if ($partId) {
                $docPayload['parts'][0]['id'] = $partId;
            }
            if (!empty($sigProfileId)) {
                $docPayload['signatureProfileId'] = $sigProfileId;
            }
            // Zone de signature par défaut en bas à droite de la dernière page.
            $docPayload['pdfSignatureFields'] = [[
                'imagePage'   => -1,
                'imageX'      => 390.0,
                'imageY'      => 710.0,
                'imageWidth'  => 150.0,
                'imageHeight' => 80.0,
            ]];

            try {
                $docResp = $client->post("{$endpoint}/api/workflows/{$workflowId}/documents", $docPayload);
                Log::info('SunnyStamp: création document via /documents', [
                    'workflow_id' => $workflowId,
                    'status' => $docResp->status(),
                    'body_excerpt' => substr($docResp->body(), 0, 200),
                ]);
                // Non bloquant: certaines instances créent le doc automatiquement via createDocuments=true.
            } catch (\Throwable $e) {
                Log::warning('SunnyStamp: exception création document /documents', [
                    'workflow_id' => $workflowId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Fallback multipart si raw binary a échoué.
        if (!$uploadResp || !$uploadResp->successful()) {
            foreach ([
                ['field' => 'document', 'url' => $uploadUrlWithQuery],
                ['field' => 'file',     'url' => $uploadUrlWithQuery],
                ['field' => 'document', 'url' => $uploadUrlBase],
            ] as $attempt) {
                try {
                    $candidate = $client
                        ->attach($attempt['field'], $pdfBytes, $fileName, ['Content-Type' => 'application/pdf'])
                        ->post($attempt['url']);

                    Log::info('SunnyStamp: fallback upload multipart', [
                        'workflow_id' => $workflowId,
                        'field' => $attempt['field'],
                        'url'   => $attempt['url'],
                        'status' => $candidate->status(),
                    ]);

                    if ($candidate->successful()) {
                        $uploadResp = $candidate;
                        break;
                    }
                    $uploadResp = $candidate;
                    if (in_array($candidate->status(), [401, 403], true)) {
                        break;
                    }
                } catch (\Throwable $e) {
                    Log::warning('SunnyStamp: exception fallback upload multipart', [
                        'workflow_id' => $workflowId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        if (!$uploadResp || !$uploadResp->successful()) {
            if (!$uploadResp) {
                $this->lastPlatformError = 'upload_document: aucune réponse exploitable reçue';
                Log::error('SunnyStamp: échec upload document (aucune réponse)', ['workflow_id' => $workflowId]);
                return null;
            }
            $this->lastPlatformError = 'upload_document: ' . self::formatApiErrorDetail($uploadResp);
            Log::error('SunnyStamp: échec upload document', [
                'status' => $uploadResp->status(),
                'body' => $uploadResp->body(),
            ]);
            return null;
        }

        // 3. Démarrer le workflow
        // Certaines versions API rejettent PATCH application/json (HTTP 415).
        // On tente plusieurs variantes compatibles avant d'échouer.
        // Certaines APIs utilisent 'status' plutôt que 'workflowStatus'.
        $startAttempts = [
            // Variantes PATCH merge-patch
            fn() => $client
                ->withHeaders(['Content-Type' => 'application/merge-patch+json'])
                ->send('PATCH', "{$endpoint}/api/workflows/{$workflowId}", ['json' => ['workflowStatus' => 'started']]),
            fn() => $client
                ->withHeaders(['Content-Type' => 'application/merge-patch+json'])
                ->send('PATCH', "{$endpoint}/api/workflows/{$workflowId}", ['json' => ['workflowStatus' => 'in_progress']]),
            fn() => $client
                ->withHeaders(['Content-Type' => 'application/merge-patch+json'])
                ->send('PATCH', "{$endpoint}/api/workflows/{$workflowId}", ['json' => ['workflowStatus' => 'STARTED']]),

            // Variantes PATCH JSON
            fn() => $client
                ->withHeaders(['Content-Type' => 'application/json'])
                ->send('PATCH', "{$endpoint}/api/workflows/{$workflowId}", ['json' => ['workflowStatus' => 'started']]),
            fn() => $client
                ->withHeaders(['Content-Type' => 'application/json'])
                ->send('PATCH', "{$endpoint}/api/workflows/{$workflowId}", ['json' => ['workflowStatus' => 'in_progress']]),
            fn() => $client
                ->withHeaders(['Content-Type' => 'application/json'])
                ->send('PATCH', "{$endpoint}/api/workflows/{$workflowId}", ['json' => ['status' => 'started']]),
            fn() => $client
                ->withHeaders(['Content-Type' => 'application/json'])
                ->send('PATCH', "{$endpoint}/api/workflows/{$workflowId}", ['json' => ['status' => 'in_progress']]),

            // Variantes PUT
            fn() => $client
                ->withHeaders(['Content-Type' => 'application/json'])
                ->send('PUT', "{$endpoint}/api/workflows/{$workflowId}", ['json' => ['workflowStatus' => 'started']]),
            fn() => $client
                ->withHeaders(['Content-Type' => 'application/json'])
                ->send('PUT', "{$endpoint}/api/workflows/{$workflowId}", ['json' => ['workflowStatus' => 'in_progress']]),

            // Variantes POST /start
            fn() => $client
                ->withHeaders(['Content-Type' => 'application/json'])
                ->send('POST', "{$endpoint}/api/workflows/{$workflowId}/start", ['json' => []]),
            fn() => $client
                ->withHeaders(['Content-Type' => 'application/json'])
                ->send('POST', "{$endpoint}/api/workflows/{$workflowId}/start", ['json' => ['workflowStatus' => 'started']]),
            fn() => $client
                ->withHeaders(['Content-Type' => 'application/json'])
                ->send('POST', "{$endpoint}/api/workflows/{$workflowId}/start", ['json' => ['status' => 'started']]),
        ];

        $startResp = null;
        foreach ($startAttempts as $attempt) {
            try {
                $candidate = $attempt();
                if ($candidate->successful()) {
                    $startResp = $candidate;
                    break;
                }

                $startResp = $candidate;

                // On continue à tester toutes les variantes sauf erreurs d'auth/permission.
                if (in_array($candidate->status(), [401, 403], true)) {
                    break;
                }
            } catch (\Throwable $e) {
                Log::warning('SunnyStamp: tentative start workflow en exception', [
                    'workflow_id' => $workflowId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if (!$startResp || !$startResp->successful()) {
            if (!$startResp) {
                $this->lastPlatformError = 'start_workflow: aucune réponse exploitable reçue';
                Log::error('SunnyStamp: échec démarrage workflow (aucune réponse)', [
                    'workflow_id' => $workflowId,
                ]);
                return null;
            }
            if ($startResp->status() === 403 && str_contains((string) $startResp->body(), 'NoDocumentToSignInWorkflow')) {
                $this->lastPlatformError = 'start_workflow: aucun document signé détecté dans le workflow après upload';
            } else {
                $this->lastPlatformError = 'start_workflow: ' . self::formatApiErrorDetail($startResp);
            }
            Log::error('SunnyStamp: échec démarrage workflow', [
                'status' => $startResp->status(), 'body' => $startResp->body(),
            ]);
            return null;
        }

        // Certaines instances renvoient deja un lien/token de signature dans la reponse start.
        $startJson = $startResp->json();
        if (is_array($startJson)) {
            $startUrl = $this->extractInviteUrl($startJson, $endpoint);
            if (is_string($startUrl) && $startUrl !== '') {
                Log::info('SunnyStamp: URL récupérée via réponse start', [
                    'workflow_id' => $workflowId,
                    'url' => $startUrl,
                ]);
                return $startUrl;
            }
        }

        $startBodyUrl = self::extractFirstUrlFromText((string) $startResp->body());
        if (is_string($startBodyUrl) && $startBodyUrl !== '') {
            Log::info('SunnyStamp: URL récupérée via body start (texte brut)', [
                'workflow_id' => $workflowId,
                'url' => $startBodyUrl,
            ]);
            return $startBodyUrl;
        }

        // 4. Créer le lien d'invitation
        $recipientIdentity = array_filter([
            'id' => $recipientPlatformUserId,
            'userId' => $recipientPlatformUserId,
            'email' => $signer->email,
            'firstName' => $recipientFirstName,
            'lastName' => $recipientLastName,
            'name' => (string) $signer->name,
            'phoneNumber' => $recipientPhone,
        ], fn($v) => !is_null($v) && $v !== '');

        $inviteAttempts = [
            // Endpoints officiels SunnyStamp d'abord.
            [
                'label' => 'POST /api/workflows/{workflowId}/sendInvite recipientEmail',
                'method' => 'POST',
                'url' => "{$endpoint}/api/workflows/{$workflowId}/sendInvite",
                'payloadType' => 'json',
                'payload' => ['recipientEmail' => $signer->email],
            ],
            [
                'label' => 'POST /api/workflows/{workflowId}/sendInvite email',
                'method' => 'POST',
                'url' => "{$endpoint}/api/workflows/{$workflowId}/sendInvite",
                'payloadType' => 'json',
                'payload' => ['email' => $signer->email],
            ],
            [
                'label' => 'POST /api/workflows/{workflowId}/invite recipientEmail',
                'method' => 'POST',
                'url' => "{$endpoint}/api/workflows/{$workflowId}/invite",
                'payloadType' => 'json',
                'payload' => ['recipientEmail' => $signer->email],
            ],
            [
                'label' => 'POST /api/workflows/{workflowId}/invite email',
                'method' => 'POST',
                'url' => "{$endpoint}/api/workflows/{$workflowId}/invite",
                'payloadType' => 'json',
                'payload' => ['email' => $signer->email],
            ],
            [
                'label' => 'POST /api/workflows/{workflowId}/invite recipient',
                'method' => 'POST',
                'url' => "{$endpoint}/api/workflows/{$workflowId}/invite",
                'payloadType' => 'json',
                'payload' => ['recipient' => $recipientIdentity],
            ],
            [
                'label' => 'POST /api/workflows/{workflowId}/invite recipientId',
                'method' => 'POST',
                'url' => "{$endpoint}/api/workflows/{$workflowId}/invite",
                'payloadType' => 'json',
                'payload' => ['recipientId' => $recipientPlatformUserId],
            ],
            [
                'label' => 'POST /api/workflows/{workflowId}/invite userId',
                'method' => 'POST',
                'url' => "{$endpoint}/api/workflows/{$workflowId}/invite",
                'payloadType' => 'json',
                'payload' => ['userId' => $recipientPlatformUserId],
            ],
            [
                'label' => 'POST /api/workflows/{workflowId}/invite/',
                'method' => 'POST',
                'url' => "{$endpoint}/api/workflows/{$workflowId}/invite/",
                'payloadType' => 'json',
                'payload' => ['recipientEmail' => $signer->email],
            ],
            [
                'label' => 'GET /api/workflows/{workflowId}/invite recipientEmail',
                'method' => 'GET',
                'url' => "{$endpoint}/api/workflows/{$workflowId}/invite",
                'payloadType' => 'query',
                'payload' => ['recipientEmail' => $signer->email],
            ],

            // Variantes sous /api/users/{ownerUserId}/workflows/{workflowId}
            [
                'label' => 'POST /api/users/{ownerUserId}/workflows/{workflowId}/sendInvite recipientEmail',
                'method' => 'POST',
                'url' => "{$endpoint}/api/users/{$ownerUserId}/workflows/{$workflowId}/sendInvite",
                'payloadType' => 'json',
                'payload' => ['recipientEmail' => $signer->email],
            ],
            [
                'label' => 'POST /api/users/{ownerUserId}/workflows/{workflowId}/invite recipientEmail',
                'method' => 'POST',
                'url' => "{$endpoint}/api/users/{$ownerUserId}/workflows/{$workflowId}/invite",
                'payloadType' => 'json',
                'payload' => ['recipientEmail' => $signer->email],
            ],
            [
                'label' => 'POST /api/users/{ownerUserId}/workflows/{workflowId}/invite email',
                'method' => 'POST',
                'url' => "{$endpoint}/api/users/{$ownerUserId}/workflows/{$workflowId}/invite",
                'payloadType' => 'json',
                'payload' => ['email' => $signer->email],
            ],
            [
                'label' => 'GET /api/users/{ownerUserId}/workflows/{workflowId}/invite recipientEmail',
                'method' => 'GET',
                'url' => "{$endpoint}/api/users/{$ownerUserId}/workflows/{$workflowId}/invite",
                'payloadType' => 'query',
                'payload' => ['recipientEmail' => $signer->email],
            ],
            [
                'label' => 'POST /api/users/{ownerUserId}/workflows/{workflowId}/invite-link recipientEmail',
                'method' => 'POST',
                'url' => "{$endpoint}/api/users/{$ownerUserId}/workflows/{$workflowId}/invite-link",
                'payloadType' => 'json',
                'payload' => ['recipientEmail' => $signer->email],
            ],
            [
                'label' => 'POST /api/users/{ownerUserId}/workflows/{workflowId}/invite-link recipientId',
                'method' => 'POST',
                'url' => "{$endpoint}/api/users/{$ownerUserId}/workflows/{$workflowId}/invite-link",
                'payloadType' => 'json',
                'payload' => ['recipientId' => $recipientPlatformUserId],
            ],

            // Autres variantes observées sur certains tenants.
            [
                'label' => 'POST /api/workflows/{workflowId}/invites recipientEmail',
                'method' => 'POST',
                'url' => "{$endpoint}/api/workflows/{$workflowId}/invites",
                'payloadType' => 'json',
                'payload' => ['recipientEmail' => $signer->email],
            ],
            [
                'label' => 'POST /api/workflows/{workflowId}/invites recipient',
                'method' => 'POST',
                'url' => "{$endpoint}/api/workflows/{$workflowId}/invites",
                'payloadType' => 'json',
                'payload' => ['recipient' => $recipientIdentity],
            ],
            [
                'label' => 'POST /api/workflows/{workflowId}/invite-link recipientEmail',
                'method' => 'POST',
                'url' => "{$endpoint}/api/workflows/{$workflowId}/invite-link",
                'payloadType' => 'json',
                'payload' => ['recipientEmail' => $signer->email],
            ],
            [
                'label' => 'POST /api/users/{ownerUserId}/invite workflowId+recipientEmail',
                'method' => 'POST',
                'url' => "{$endpoint}/api/users/{$ownerUserId}/invite",
                'payloadType' => 'json',
                'payload' => [
                    'workflowId' => $workflowId,
                    'recipientEmail' => $signer->email,
                ],
            ],
            [
                'label' => 'POST /api/users/{ownerUserId}/invite workflowId+email',
                'method' => 'POST',
                'url' => "{$endpoint}/api/users/{$ownerUserId}/invite",
                'payloadType' => 'json',
                'payload' => [
                    'workflowId' => $workflowId,
                    'email' => $signer->email,
                ],
            ],
        ];

        $inviteResp = null;
        $inviteAttemptTrace = [];
        foreach ($inviteAttempts as $attempt) {
            $label = (string) ($attempt['label'] ?? 'attempt');

            try {
                $method = strtoupper((string) ($attempt['method'] ?? 'POST'));
                $url = (string) ($attempt['url'] ?? '');
                $payloadType = (string) ($attempt['payloadType'] ?? 'json');
                $payload = (array) ($attempt['payload'] ?? []);

                if ($method === 'GET' || $payloadType === 'query') {
                    $candidate = $client->send($method, $url, ['query' => $payload]);
                } else {
                    $candidate = $client->send($method, $url, ['json' => $payload]);
                }

                $candidateJson = $candidate->json();
                $candidateApiCode = null;
                if (is_array($candidateJson)) {
                    $candidateApiCode = $candidateJson['code']
                        ?? $candidateJson['errorCode']
                        ?? $candidateJson['error']
                        ?? null;
                }

                $traceLine = $label . ' => HTTP ' . $candidate->status();
                if (is_string($candidateApiCode) && $candidateApiCode !== '') {
                    $traceLine .= ' [' . Str::limit($candidateApiCode, 60, '...') . ']';
                }
                $inviteAttemptTrace[] = $traceLine;

                if ($candidate->successful()) {
                    $inviteResp = $candidate;
                    break;
                }

                // Continuer sur endpoints non trouvés / méthode non supportée / payload refusé.
                if (!in_array($candidate->status(), [400, 404, 405, 415], true)) {
                    $inviteResp = $candidate;
                    break;
                }

                $inviteResp = $candidate;
            } catch (\Throwable $e) {
                $inviteAttemptTrace[] = $label . ' => EXCEPTION ' . Str::limit($e->getMessage(), 100, '...');
                Log::warning('SunnyStamp: tentative invite en exception', [
                    'workflow_id' => $workflowId,
                    'attempt_label' => $label,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $inviteAttemptSummary = implode(' | ', array_slice($inviteAttemptTrace, -12));

        if ($inviteResp && $inviteResp->successful()) {
            Log::info('SunnyStamp: réponse invite reçue', [
                'workflow_id' => $workflowId,
                'status' => $inviteResp->status(),
                'body' => $inviteResp->body(),
            ]);

            $inviteUrl = $this->extractInviteUrl($inviteResp->json(), $endpoint);

            if ((!is_string($inviteUrl) || $inviteUrl === '') && is_string($inviteResp->body())) {
                // Certaines instances renvoient une URL brute, un token brut, ou un JSON non typé.
                $inviteUrl = self::extractFirstUrlFromText((string) $inviteResp->body());

                if ((!is_string($inviteUrl) || $inviteUrl === '') && preg_match('/[A-Za-z0-9_\-]{16,}\.[A-Za-z0-9_\-]{16,}\.[A-Za-z0-9_\-]{16,}/', (string) $inviteResp->body(), $m) === 1) {
                    $inviteUrl = rtrim($endpoint, '/') . '/invite?token=' . ($m[0] ?? '');
                }
            }

            if (is_string($inviteUrl) && $inviteUrl !== '') {
                return $inviteUrl;
            }

            $this->lastPlatformError = 'invite: réponse sans URL exploitable - ' . self::formatApiErrorDetail($inviteResp)
                . ' | attempts=' . ($inviteAttemptSummary !== '' ? $inviteAttemptSummary : 'n/a');
            Log::warning('SunnyStamp: invite créé mais URL absente', [
                'status' => $inviteResp->status(),
                'body' => $inviteResp->body(),
                'attempt_summary' => $inviteAttemptSummary,
            ]);
            return null;
        }

        if (!$inviteResp) {
            $this->lastPlatformError = 'invite: aucune réponse exploitable reçue'
                . ' | attempts=' . ($inviteAttemptSummary !== '' ? $inviteAttemptSummary : 'n/a');
            Log::error('SunnyStamp: échec invite (aucune réponse)', [
                'workflow_id' => $workflowId,
                'attempt_summary' => $inviteAttemptSummary,
            ]);
            return null;
        }

        // Fallback quand les endpoints invite n'existent pas sur l'instance (404).
        if ($inviteResp->status() === 404) {
            try {
                // Logguer la réponse 404 pour diagnostic
                Log::warning('SunnyStamp: invite 404 - tentative fallback workflow details', [
                    'workflow_id' => $workflowId,
                    'last_invite_status' => $inviteResp->status(),
                    'last_invite_body' => Str::limit((string) $inviteResp->body(), 300),
                ]);

                $workflowFallbackTrace = [];

                $workflowResp = $client->get("{$endpoint}/api/workflows/{$workflowId}");
                $workflowFallbackTrace[] = 'GET /api/workflows/{workflowId} => HTTP ' . $workflowResp->status();
                if ($workflowResp->successful()) {
                    Log::info('SunnyStamp: détail workflow reçu pour extraction invite URL', [
                        'workflow_id' => $workflowId,
                        'body' => $workflowResp->body(),
                    ]);

                    $workflowUrl = $this->extractInviteUrl($workflowResp->json(), $endpoint);

                    if (is_string($workflowUrl) && $workflowUrl !== '') {
                        Log::info('SunnyStamp: URL récupérée via détail workflow', [
                            'workflow_id' => $workflowId,
                            'url' => $workflowUrl,
                        ]);
                        return $workflowUrl;
                    }
                }

                // Variante read sous espace users/{ownerId} pour instances multi-routes.
                $workflowByUserResp = $client->get("{$endpoint}/api/users/{$ownerUserId}/workflows/{$workflowId}");
                $workflowFallbackTrace[] = 'GET /api/users/{ownerUserId}/workflows/{workflowId} => HTTP ' . $workflowByUserResp->status();
                if ($workflowByUserResp->successful()) {
                    $workflowByUserUrl = $this->extractInviteUrl($workflowByUserResp->json(), $endpoint);
                    if (is_string($workflowByUserUrl) && $workflowByUserUrl !== '') {
                        Log::info('SunnyStamp: URL récupérée via détail workflow users/{id}', [
                            'workflow_id' => $workflowId,
                            'url' => $workflowByUserUrl,
                        ]);
                        return $workflowByUserUrl;
                    }
                }

                $inviteAttemptSummary .= ($inviteAttemptSummary !== '' ? ' | ' : '') . implode(' | ', $workflowFallbackTrace);
            } catch (\Throwable $e) {
                Log::warning('SunnyStamp: fallback détail workflow en exception', [
                    'workflow_id' => $workflowId,
                    'error' => $e->getMessage(),
                ]);
            }

            // Eviter de retourner un lien approximatif qui mène à "invitation invalide".
            // On échoue explicitement pour forcer un diagnostic côté API.
            $this->lastPlatformError = 'invite: aucun lien exploitable trouvé (endpoints invite en 404 et workflow sans URL/token)'
                . ' | attempts=' . ($inviteAttemptSummary !== '' ? $inviteAttemptSummary : 'n/a');
            Log::warning('SunnyStamp: aucun lien invite exploitable trouvé après fallback', [
                'workflow_id' => $workflowId,
                'endpoint' => $endpoint,
                'attempt_summary' => $inviteAttemptSummary,
            ]);
            return null;
        }

        Log::warning('SunnyStamp: invite non créé', [
            'status' => $inviteResp->status(), 'body' => $inviteResp->body(),
            'attempt_summary' => $inviteAttemptSummary,
        ]);
        $this->lastPlatformError = 'invite: ' . self::formatApiErrorDetail($inviteResp)
            . ' | attempts=' . ($inviteAttemptSummary !== '' ? $inviteAttemptSummary : 'n/a');
        return null;
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

        $executionId = $request->input('execution_id');
        Log::info('SunnyStamp: getSignatureInviteUrl début', [
            'execution_id' => $executionId,
            'action_type' => $request->input('action_type'),
            'user_id' => Auth::id(),
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
        self::$lastLookupApiError = null;
        $platformUserId = self::resolvePlatformUserIdByEmail($cfg, $currentUser->email);
        if (!$platformUserId) {
            // Fallback: certaines plateformes n'autorisent pas la recherche utilisateur par email.
            // On utilise alors l'utilisateur owner du token API pour créer le workflow.
            $platformUserId = self::resolvePlatformOwnerUserId($cfg);
        }
        if (!$platformUserId) {
            $apiDetail = self::$lastLookupApiError
                ? ' [Erreur API: ' . self::$lastLookupApiError . ']'
                : '';
            Log::warning('SunnyStamp: compte plateforme introuvable pour utilisateur', [
                'user_id'    => $currentUser->id,
                'email'      => $currentUser->email,
                'admin_id'   => $currentUser->profile?->administration_id,
                'endpoint'   => $cfg->endpoint,
                'api_error'  => self::$lastLookupApiError,
            ]);
            return response()->json([
                'ok'      => false,
                'message' => 'Impossible de trouver votre compte sur la plateforme de signature. Vérifiez que l\'adresse e-mail ' . $currentUser->email . ' est bien enregistrée sur la plateforme.' . $apiDetail,
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
            $msg = self::toBusinessPlatformMessage($this->lastPlatformError);
            if (!empty($this->lastPlatformError)) {
                $msg .= ' Détail: ' . $this->lastPlatformError;
            }
            return response()->json([
                'ok'      => false,
                'message' => $msg,
            ], 500);
        }

        // Stocker le platform_workflow_id dans l'exécution pour le suivi de statut.
        Log::info('SunnyStamp: avant enregistrement platform_workflow_id', [
            'execution_id' => $execution->id,
            'lastPlatformWorkflowId' => $this->lastPlatformWorkflowId,
            'inviteUrl' => substr($inviteUrl ?? '', 0, 50),
            'condition' => $this->lastPlatformWorkflowId ? 'TRUE' : 'FALSE',
        ]);

        if ($this->lastPlatformWorkflowId) {
            $execution->update([
                'platform_workflow_id' => $this->lastPlatformWorkflowId,
                'platform_status'      => 'started',
            ]);
            Log::info('SunnyStamp: platform_workflow_id enregistré ✅', [
                'execution_id' => $execution->id,
                'platform_workflow_id' => $this->lastPlatformWorkflowId,
                'updated_at' => now()->toIso8601String(),
            ]);
        } else {
            Log::error('SunnyStamp: platform_workflow_id est NULL/vide ❌, pas enregistré', [
                'execution_id' => $execution->id,
                'lastPlatformWorkflowId' => $this->lastPlatformWorkflowId,
                'action_type' => $request->input('action_type'),
                'inviteUrl_exists' => is_string($inviteUrl) && $inviteUrl !== '',
            ]);
        }

        return response()->json([
            'ok'                  => true,
            'url'                 => $inviteUrl,
            'platform_workflow_id' => $this->lastPlatformWorkflowId,
        ]);
    }

    /**
     * AJAX — Récupère le statut actuel du workflow sur la plateforme de signature.
     * GET /signatures/platform-status/{execution}
     */
    public function getPlatformWorkflowStatus(string $executionId): JsonResponse
    {
        $execution = WorkflowExecution::find($executionId);
        if (!$execution) {
            return response()->json(['ok' => false, 'message' => 'Exécution introuvable.'], 404);
        }

        // Vérifier que l'utilisateur a le droit de voir cette exécution
        $wf = $execution->workflow()->with('steps')->first();
        $steps = $wf?->steps?->sortBy('order') ?? collect();
        $stepObj = $steps->firstWhere('order', (int) ($execution->current_step ?? 1));
        $userId = Auth::id();
        if ($stepObj?->assignee_id && $stepObj->assignee_id !== $userId) {
            return response()->json(['ok' => false, 'message' => 'Accès refusé.'], 403);
        }

        // Retourner le statut local s'il n'y a pas de platform_workflow_id.
        if (empty($execution->platform_workflow_id)) {
            return response()->json([
                'ok'              => true,
                'local_status'    => $execution->status,
                'platform_status' => null,
                'phase'           => self::mapExecutionPhase($execution->status, null),
            ]);
        }

        // Interroger la plateforme pour le statut temps réel.
        $cfg = $this->resolveSignatureConfig();
        if (!$cfg) {
            return response()->json([
                'ok'              => true,
                'local_status'    => $execution->status,
                'platform_status' => $execution->platform_status,
                'phase'           => self::mapExecutionPhase($execution->status, $execution->platform_status),
            ]);
        }

        $endpoint  = rtrim((string) $cfg->endpoint, '/');
        $apiToken  = (string) ($cfg->api_key ?? $cfg->api_token ?? '');
        if ($apiToken === '') {
            Log::warning('SunnyStamp: getPlatformWorkflowStatus sans token API');
            return response()->json([
                'ok'              => true,
                'local_status'    => $execution->status,
                'platform_status' => $execution->platform_status,
                'phase'           => self::mapExecutionPhase($execution->status, $execution->platform_status),
            ]);
        }
        $client    = Http::withToken($apiToken)->timeout(10)->acceptJson();

        $platformStatus = null;
        $platformPhase  = null;
        try {
            // 1. GET /api/workflows/{wflId} → champ workflowStatus (API ARTCI/UVCI)
            $resp = $client->get("{$endpoint}/api/workflows/{$execution->platform_workflow_id}");
            Log::info('SunnyStamp: polling workflow status', [
                'execution_id' => $executionId,
                'platform_workflow_id' => $execution->platform_workflow_id,
                'http_status' => $resp->status(),
                'body_excerpt' => substr($resp->body(), 0, 300),
            ]);

            if ($resp->successful()) {
                $data = $resp->json();
                // workflowStatus est le champ principal selon la doc SunnyStamp/ARTCI.
                $platformStatus = $data['workflowStatus'] ?? $data['status'] ?? $data['state'] ?? null;
            }

            // 2. Fallback via GET /api/notifications?items.workflowId={wflId}
            //    pour détecter workflowFinished/recipientFinished même si GET /workflows ne suffit pas.
            if (!$platformStatus || !in_array(strtolower((string)$platformStatus), ['finished','completed','done','refused','rejected'], true)) {
                try {
                    $notifResp = $client->get("{$endpoint}/api/notifications", [
                        'items.workflowId' => $execution->platform_workflow_id,
                    ]);
                    if ($notifResp->successful()) {
                        $notifItems = $notifResp->json('items') ?? $notifResp->json('data') ?? ($notifResp->json() ?? []);
                        $notifItems = is_array($notifItems) ? $notifItems : [];
                        // Trier par date décroissante pour avoir l'événement le plus récent.
                        usort($notifItems, fn($a, $b) => strcmp(
                            (string)($b['createdAt'] ?? $b['date'] ?? ''),
                            (string)($a['createdAt'] ?? $a['date'] ?? '')
                        ));
                        foreach ($notifItems as $notif) {
                            $eventType = (string)($notif['eventType'] ?? $notif['type'] ?? $notif['event'] ?? '');
                            if (in_array($eventType, ['workflowFinished','workflow_finished','WORKFLOW_FINISHED'], true)) {
                                $platformStatus = 'finished';
                                Log::info('SunnyStamp: workflowFinished détecté via /notifications', [
                                    'execution_id' => $executionId,
                                    'event' => $eventType,
                                ]);
                                break;
                            }
                            if (in_array($eventType, ['recipientFinished','recipient_finished'], true)) {
                                $platformStatus = $platformStatus ?? 'signing';
                            }
                        }
                    }
                } catch (\Throwable $ne) {
                    Log::debug('SunnyStamp: /notifications non accessible', ['error' => $ne->getMessage()]);
                }
            }

            // 3. Fallback via GET /api/webhookEvents/?items.workflowId={wflId}&items.eventType=workflowFinished
            if (!$platformStatus || strtolower((string)$platformStatus) === 'started') {
                try {
                    $wbResp = $client->get("{$endpoint}/api/webhookEvents/", [
                        'items.workflowId' => $execution->platform_workflow_id,
                        'items.eventType'  => 'workflowFinished',
                    ]);
                    if ($wbResp->successful()) {
                        $wbItems = $wbResp->json('items') ?? $wbResp->json('data') ?? ($wbResp->json() ?? []);
                        if (!empty($wbItems)) {
                            $platformStatus = 'finished';
                            Log::info('SunnyStamp: workflowFinished détecté via /webhookEvents', [
                                'execution_id' => $executionId,
                            ]);
                        }
                    }
                } catch (\Throwable $we) {
                    Log::debug('SunnyStamp: /webhookEvents non accessible', ['error' => $we->getMessage()]);
                }
            }

            // 4. Appliquer les transitions locales si statut changé.
            if (is_string($platformStatus) && $platformStatus !== '' && $platformStatus !== $execution->platform_status) {
                $platformPhase = self::mapExecutionPhase($execution->status, $platformStatus);
                $updates = ['platform_status' => $platformStatus];

                $doneStatuses = ['finished', 'completed', 'done', 'FINISHED', 'COMPLETED', 'DONE'];
                $isNowDone = in_array($platformStatus, $doneStatuses, true);
                if ($isNowDone && $execution->status !== 'completed') {
                    $updates['status']       = 'completed';
                    $updates['completed_at'] = now();
                    if ($execution->document_id) {
                        Document::find($execution->document_id)?->update(['status' => 'signed', 'signed_at' => now()]);
                    }
                    Log::info('SunnyStamp: exécution transitionnée vers completed', [
                        'execution_id' => $executionId,
                        'platform_status' => $platformStatus,
                    ]);
                }

                $execution->update($updates);
                $execution->refresh();

                // Télécharger le document signé depuis la plateforme.
                if ($isNowDone) {
                    $this->downloadSignedDocumentFromPlatform(
                        $execution->fresh(),
                        $endpoint,
                        $apiToken,
                        (bool) ($cfg->verify_ssl ?? true)
                    );
                }
            }

            // Vérifier aussi si déjà completed mais signed_file_path absent (rattrapage).
            if ($execution->status === 'completed' && $execution->document_id) {
                $doc = Document::find($execution->document_id);
                if ($doc && empty($doc->signed_file_path)) {
                    $this->downloadSignedDocumentFromPlatform(
                        $execution,
                        $endpoint,
                        $apiToken,
                        (bool) ($cfg->verify_ssl ?? true)
                    );
                }
            }

            $platformPhase = $platformPhase ?? self::mapExecutionPhase($execution->status, is_string($platformStatus) ? $platformStatus : null);

        } catch (\Throwable $e) {
            Log::warning('SunnyStamp: getPlatformWorkflowStatus exception', [
                'execution_id' => $executionId,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'ok'                  => true,
            'local_status'        => $execution->fresh()->status,
            'platform_status'     => $platformStatus ?? $execution->platform_status,
            'platform_workflow_id' => $execution->platform_workflow_id,
            'phase'               => $platformPhase ?? self::mapExecutionPhase($execution->status, $execution->platform_status),
        ]);
    }

    /**
     * Télécharge le document signé depuis la plateforme ARTCI-Sign et le sauvegarde localement.
     * Appelé automatiquement quand le workflow passe à l'état "finished".
     * GET /api/workflows/{wflId}/downloadDocuments
     */
    private function downloadSignedDocumentFromPlatform(WorkflowExecution $execution, string $endpoint, string $token, bool $verifySSL = true): void
    {
        $platformWorkflowId = $execution->platform_workflow_id;
        if (!$platformWorkflowId || !$execution->document_id) {
            return;
        }

        $document = Document::find($execution->document_id);
        if (!$document) {
            return;
        }

        // Éviter de re-télécharger si déjà récupéré.
        if (!empty($document->signed_file_path)) {
            Log::info('SunnyStamp: document signé déjà téléchargé', [
                'execution_id' => $execution->id,
                'signed_file_path' => $document->signed_file_path,
            ]);
            return;
        }

        try {
            $downloadResp = Http::withToken($token)
                ->timeout(60)
                ->when(!$verifySSL, fn($h) => $h->withoutVerifying())
                ->get("{$endpoint}/api/workflows/{$platformWorkflowId}/downloadDocuments");

            Log::info('SunnyStamp: téléchargement document signé', [
                'execution_id'       => $execution->id,
                'platform_workflow_id' => $platformWorkflowId,
                'http_status'        => $downloadResp->status(),
                'content_type'       => $downloadResp->header('Content-Type'),
                'content_length'     => $downloadResp->header('Content-Length'),
            ]);

            if (!$downloadResp->successful()) {
                Log::error('SunnyStamp: échec téléchargement document signé', [
                    'execution_id' => $execution->id,
                    'status'       => $downloadResp->status(),
                    'body_excerpt' => substr($downloadResp->body(), 0, 300),
                ]);
                return;
            }

            $pdfContent = $downloadResp->body();
            if (empty($pdfContent) || strlen($pdfContent) < 100) {
                Log::warning('SunnyStamp: document signé vide ou trop petit', [
                    'execution_id' => $execution->id,
                    'size' => strlen($pdfContent),
                ]);
                return;
            }

            // Sauvegarder dans storage/app/public/signed_documents/
            $originalName = pathinfo($document->file_path ?? 'document.pdf', PATHINFO_FILENAME);
            $filename     = 'signed_' . $originalName . '_' . now()->format('Ymd_His') . '.pdf';
            $storagePath  = 'signed_documents/' . $filename;

            Storage::disk('public')->makeDirectory('signed_documents');
            Storage::disk('public')->put($storagePath, $pdfContent);

            // Mettre à jour le document en base.
            $document->update([
                'signed_file_path' => $storagePath,
                'status'           => 'signed',
                'signed_at'        => now(),
            ]);

            Log::info('SunnyStamp: document signé sauvegardé ✅', [
                'execution_id'     => $execution->id,
                'document_id'      => $document->id,
                'signed_file_path' => $storagePath,
                'file_size'        => strlen($pdfContent),
            ]);

        } catch (\Throwable $e) {
            Log::error('SunnyStamp: exception téléchargement document signé', [
                'execution_id' => $execution->id,
                'error'        => $e->getMessage(),
            ]);
        }
    }

    /**
     * Sert le document signé téléchargé depuis la plateforme.
     * GET /signatures/signed-document/{executionId}
     */
    public function serveSignedDocument(string $executionId): \Symfony\Component\HttpFoundation\Response
    {
        $execution = WorkflowExecution::find($executionId);
        if (!$execution) {
            abort(404, 'Exécution introuvable.');
        }

        $document = $execution->document_id ? Document::find($execution->document_id) : null;
        if (!$document || empty($document->signed_file_path)) {
            abort(404, 'Document signé non disponible.');
        }

        if (!Storage::disk('public')->exists($document->signed_file_path)) {
            abort(404, 'Fichier introuvable sur le serveur.');
        }

        $filename = 'signed_' . Str::slug($document->title ?? 'document') . '.pdf';
        return Storage::disk('public')->download($document->signed_file_path, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    /**
     * Webhook — Reçoit les événements de la plateforme de signature.
     * POST /api/signature/platform-webhook
     * Sans authentification session, sans CSRF, mais valider via secret HMAC si configuré.
     */
    public function platformWebhook(Request $request): JsonResponse
    {
        $payload = $request->all();

        Log::info('SunnyStamp: webhook reçu', [
            'payload' => $payload,
            'headers' => $request->headers->all(),
        ]);

        // Extraire l'ID du workflow plateforme depuis la payload.
        $platformWorkflowId = $payload['workflowId']
            ?? $payload['workflow_id']
            ?? ($payload['workflow']['id'] ?? null)
            ?? ($payload['data']['workflowId'] ?? null)
            ?? null;

        if (!is_string($platformWorkflowId) || $platformWorkflowId === '') {
            Log::warning('SunnyStamp: webhook sans workflowId exploitable', ['payload' => $payload]);
            return response()->json(['ok' => true, 'note' => 'no workflowId']);
        }

        $execution = WorkflowExecution::where('platform_workflow_id', $platformWorkflowId)->first();
        if (!$execution) {
            Log::warning('SunnyStamp: webhook - aucune execution locale pour platform_workflow_id', [
                'platform_workflow_id' => $platformWorkflowId,
            ]);
            return response()->json(['ok' => true, 'note' => 'execution not found']);
        }

        $event          = (string) ($payload['event'] ?? $payload['type'] ?? $payload['eventType'] ?? '');
        $platformStatus = (string) ($payload['workflowStatus']
            ?? $payload['status']
            ?? ($payload['workflow']['status'] ?? '')
            ?? ($payload['workflow']['workflowStatus'] ?? ''));

        $updates = [];
        if ($platformStatus !== '') {
            $updates['platform_status'] = $platformStatus;
        }

        // Transitions de statut locales selon l'événement plateforme.
        $doneEvents    = ['workflowFinished', 'workflow_finished', 'WORKFLOW_FINISHED'];
        $refusedEvents = ['recipientRefused', 'recipient_refused', 'RECIPIENT_REFUSED'];
        $signingEvents = ['recipientStarted', 'recipient_started', 'RECIPIENT_STARTED', 'signingStarted', 'signing_started'];
        $doneStatuses  = ['FINISHED', 'COMPLETED', 'DONE', 'finished', 'completed', 'done'];

        if (in_array($event, $doneEvents, true) || in_array($platformStatus, $doneStatuses, true)) {
            if ($execution->status === 'in_progress') {
                $updates['status'] = 'completed';
                $updates['completed_at'] = now();

                if ($execution->document_id) {
                    Document::find($execution->document_id)?->update(['status' => 'signed', 'signed_at' => now()]);
                }

                // Notifier l'utilisateur que le workflow est terminé.
                try {
                    $wf = $execution->workflow()->with('steps', 'creator')->first();
                    if ($wf?->created_by) {
                        Notification::create([
                            'user_id'  => $wf->created_by,
                            'type'     => 'workflow_completed',
                            'title'    => 'Workflow de signature terminé',
                            'message'  => 'Le workflow « ' . ($wf->name ?? 'Sans titre') . ' » a été signé avec succès sur la plateforme.',
                            'data'     => json_encode([
                                'execution_id'         => $execution->id,
                                'platform_workflow_id' => $platformWorkflowId,
                                'event'                => $event,
                            ]),
                        ]);
                    }
                } catch (\Throwable $e) {
                    Log::warning('SunnyStamp: webhook - notification création échouée', ['error' => $e->getMessage()]);
                }
            }
        } elseif (in_array($event, $refusedEvents, true)) {
            $updates['status'] = 'rejected';
            try {
                $wf = $execution->workflow()->with('creator')->first();
                if ($wf?->created_by) {
                    Notification::create([
                        'user_id' => $wf->created_by,
                        'type'    => 'workflow_rejected',
                        'title'   => 'Workflow de signature refusé',
                        'message' => 'Un signataire a refusé de signer le workflow « ' . ($wf->name ?? 'Sans titre') . ' ».',
                        'data'    => json_encode([
                            'execution_id'         => $execution->id,
                            'platform_workflow_id' => $platformWorkflowId,
                            'event'                => $event,
                        ]),
                    ]);
                }
            } catch (\Throwable $e) {
                Log::warning('SunnyStamp: webhook - notification refus échouée', ['error' => $e->getMessage()]);
            }
        } elseif (in_array($event, $signingEvents, true)) {
            // Passage à la phase de signature : mettre à jour platform_status seulement.
            if ($platformStatus === '') {
                $updates['platform_status'] = 'signing';
            }
        }

        if (!empty($updates)) {
            $execution->update($updates);
        }

        // Télécharger le document signé si le workflow vient de se terminer.
        $justCompleted = !empty($updates['status']) && $updates['status'] === 'completed';
        if ($justCompleted) {
            $cfg = $this->resolveSignatureConfig();
            if ($cfg) {
                $this->downloadSignedDocumentFromPlatform(
                    $execution->fresh(),
                    rtrim((string) $cfg->endpoint, '/'),
                    (string) ($cfg->api_key ?? $cfg->api_token ?? ''),
                    (bool) ($cfg->verify_ssl ?? true)
                );
            }
        }

        Log::info('SunnyStamp: webhook traité', [
            'platform_workflow_id' => $platformWorkflowId,
            'event'                => $event,
            'platform_status'      => $platformStatus,
            'updates'              => $updates,
        ]);

        return response()->json(['ok' => true]);
    }

    /**
     * Endpoint de diagnostic — check colonne platform_workflow_id dans DB
     * GET /api/signature/diag/{executionId}
     */
    public function diagExecution(string $executionId): JsonResponse
    {
        $execution = WorkflowExecution::find($executionId);
        if (!$execution) {
            return response()->json(['ok' => false, 'message' => 'Execution introuvable']);
        }

        return response()->json([
            'ok'                  => true,
            'execution_id'        => $execution->id,
            'workflow_id'         => $execution->workflow_id,
            'document_id'         => $execution->document_id,
            'current_step'        => $execution->current_step,
            'status'              => $execution->status,
            'platform_workflow_id' => $execution->platform_workflow_id,
            'platform_status'     => $execution->platform_status,
            'step_data'           => $execution->step_data,
            'started_at'          => $execution->started_at?->toIso8601String(),
            'completed_at'        => $execution->completed_at?->toIso8601String(),
        ]);
    }

    /**
     * Endpoint de diagnostic — test webhook manuellement
     */
    private static function mapExecutionPhase(string $localStatus, ?string $platformStatus): string
    {
        if ($localStatus === 'completed') {
            return 'completed';
        }
        if ($localStatus === 'rejected') {
            return 'rejected';
        }

        $ps = strtolower((string) $platformStatus);
        if (in_array($ps, ['finished', 'completed', 'done'], true)) {
            return 'completed';
        }
        if (in_array($ps, ['refused', 'rejected', 'recipient_refused'], true)) {
            return 'rejected';
        }
        if (str_contains($ps, 'sign') || $ps === 'signing') {
            return 'signing';
        }
        if (str_contains($ps, 'consent') || $ps === 'consent') {
            return 'consent';
        }
        if (in_array($ps, ['started', 'in_progress', 'inprogress'], true)) {
            return 'in_progress';
        }

        return 'pending';
    }
}

