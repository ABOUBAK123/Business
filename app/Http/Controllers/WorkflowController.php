<?php

namespace App\Http\Controllers;

use App\Models\Workflow;
use App\Models\WorkflowStep;
use App\Models\WorkflowExecution;
use App\Models\WorkflowTemplate;
use App\Models\SignatureRequest;
use App\Models\Notification;
use App\Models\Document;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class WorkflowController extends Controller
{
    // ── Helpers ─────────────────────────────────────────────────────────────

    private function visibleWorkflowIdsForUser(string $userId)
    {
        $notifiedWorkflowIds = Notification::where('recipient_id', $userId)
            ->whereNotNull('workflow_id')
            ->pluck('workflow_id');

        return Workflow::query()
            ->where('created_by', $userId)
            ->orWhereHas('steps', fn($q) => $q->where('assignee_id', $userId))
            ->orWhereIn('id', $notifiedWorkflowIds)
            ->pluck('id');
    }

    private function ensureWorkflowVisibility(Workflow $workflow): void
    {
        $userId = Auth::id();
        abort_unless($userId, 403);

        $isCreator = $workflow->created_by === $userId;
        $isAssignee = $workflow->steps()->where('assignee_id', $userId)->exists();
        $isNotified = Notification::where('recipient_id', $userId)
            ->where('workflow_id', $workflow->id)
            ->exists();

        abort_unless($isCreator || $isAssignee || $isNotified, 403);
    }

    private function getSignatureStepIndex(Workflow $workflow, WorkflowStep $step): ?int
    {
        if (!$step->requires_signature) {
            return null;
        }

        $signatureSteps = $workflow->steps()
            ->where('requires_signature', true)
            ->orderBy('order')
            ->get(['id']);

        $index = $signatureSteps->search(fn($signatureStep) => $signatureStep->id === $step->id);

        return $index === false ? null : $index;
    }

    private function getDocumentZoneForSignatureStep(array $docZones, string $docId, ?int $signatureStepIndex): array
    {
        $zones = $docZones[$docId] ?? [];

        if (!is_array($zones)) {
            return [];
        }

        if (array_key_exists('page', $zones)) {
            return $zones;
        }

        if ($signatureStepIndex === null) {
            return [];
        }

        $zone = $zones[$signatureStepIndex] ?? [];

        return is_array($zone) ? $zone : [];
    }

    private function createSignatureRequestsForStep(Workflow $workflow, WorkflowStep $step, array $docZones): void
    {
        if (!$step->assignee_id || !$step->requires_signature) {
            return;
        }

        $docsToSign = $workflow->docs_to_sign ?? [];
        $signatureStepIndex = $this->getSignatureStepIndex($workflow, $step);

        foreach ($docsToSign as $docId) {
            $exists = SignatureRequest::where('document_id', $docId)
                ->where('requested_to', $step->assignee_id)
                ->where('status', 'pending')
                ->exists();

            if ($exists) {
                continue;
            }

            $zone = $this->getDocumentZoneForSignatureStep($docZones, (string) $docId, $signatureStepIndex);

            SignatureRequest::create([
                'id'           => Str::uuid(),
                'document_id'  => $docId,
                'requested_by' => Auth::id(),
                'requested_to' => $step->assignee_id,
                'message'      => "Workflow: {$workflow->name} — Étape {$step->order}: " . ($step->name ?? 'Action requise'),
                'status'       => 'pending',
                'zone_page'    => $zone['page']   ?? null,
                'zone_x'       => $zone['x']      ?? null,
                'zone_y'       => $zone['y']      ?? null,
                'zone_width'   => $zone['width']  ?? $zone['w'] ?? null,
                'zone_height'  => $zone['height'] ?? $zone['h'] ?? null,
                'zone_label'   => $zone['label']  ?? null,
            ]);
        }
    }

    private function formatWorkflow(Workflow $wf): array
    {
        return [
            'id'          => $wf->id,
            'name'        => $wf->name,
            'description' => $wf->description,
            'status'      => $wf->status,
            'created_by'  => $wf->created_by,
            'docs_to_sign'   => $wf->docs_to_sign ?? [],
            'attached_docs'  => $wf->attached_docs ?? [],
            'notification_config' => null,
            'updated_at'  => $wf->updated_at?->toISOString(),
            'steps'       => ($wf->steps ?? collect())->map(fn($s) => [
                'id'                 => $s->id,
                'name'               => $s->name,
                'type'               => $s->type,
                'order'              => $s->order,
                'assignee_id'        => $s->assignee_id,
                'requires_signature' => (bool)$s->requires_signature,
                'assignee'           => $s->assignee
                    ? ['id' => $s->assignee->id, 'name' => $s->assignee->name, 'email' => $s->assignee->email]
                    : null,
            ])->values(),
            'executions' => ($wf->executions ?? collect())->map(fn($e) => [
                'id'           => $e->id,
                'workflow_id'  => $e->workflow_id,
                'status'       => $e->status,
                'current_step' => $e->current_step,
                'document_id'  => $e->document_id,
            ])->values(),
        ];
    }

    // ── Pages ────────────────────────────────────────────────────────────────

    public function index()
    {
        $userId = Auth::id();
        $workflows = Workflow::with(['steps.assignee', 'executions'])
            ->whereIn('id', $this->visibleWorkflowIdsForUser($userId))
            ->latest()->get();

        if (request()->wantsJson()) {
            return response()->json($workflows->map(fn($wf) => $this->formatWorkflow($wf))->values());
        }

        $users = User::where('status', 'active')->get(['id', 'name', 'full_name', 'email']);

        $documents = Document::where('owner_id', Auth::id())
            ->whereNull('deleted_at')
            ->where('mime_type', '!=', 'application/x-folder')
            ->latest()->get(['id', 'title', 'file_path', 'mime_type']);

        return view('workflows.index', compact('workflows', 'users', 'documents'));
    }

    public function create()
    {
        $users = User::where('status', 'active')
                     ->where('role', 'signer')
                     ->orderBy('name')
                     ->get(['id', 'name', 'email']);
        return view('workflows.create', compact('users'));
    }

    // ── CRUD Workflows ───────────────────────────────────────────────────────

    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:500',
            'steps'       => 'required|array|min:1',
            'steps.*.type'=> 'required|in:review,sign,approve,reject,notify',
            'steps.*.assignee_id' => 'nullable|exists:users,id',
        ]);

        $workflow = Workflow::create([
            'id'          => Str::uuid(),
            'name'        => $request->name,
            'description' => $request->description,
            'status'      => 'active',
            'created_by'  => Auth::id(),
            'docs_to_sign'   => $request->docs_to_sign ?? [],
            'attached_docs'  => $request->attached_docs ?? [],
        ]);

        foreach ($request->steps as $i => $stepData) {
            WorkflowStep::create([
                'id'                 => Str::uuid(),
                'workflow_id'        => $workflow->id,
                'order'              => $i + 1,
                'name'               => $stepData['name'] ?? null,
                'type'               => $stepData['type'],
                'assignee_id'        => $stepData['assignee_id'] ?? null,
                'description'        => $stepData['description'] ?? null,
                'requires_signature' => !empty($stepData['requires_signature']),
            ]);
        }

        $workflow->load(['steps.assignee', 'executions']);

        // Notifier les assignés des étapes
        NotificationService::workflowStepsAssigned($workflow, $workflow->steps, Auth::user()->name);

        if ($request->wantsJson()) {
            return response()->json(['workflow' => $this->formatWorkflow($workflow)], 201);
        }

        return redirect()->route('workflows.index')->with('success', 'Workflow créé avec succès.');
    }

    public function show(Workflow $workflow)
    {
        $this->ensureWorkflowVisibility($workflow);
        $workflow->load(['steps.assignee', 'executions.document', 'creator']);
        return view('workflows.show', compact('workflow'));
    }

    public function edit(Workflow $workflow)
    {
        abort_if($workflow->created_by !== Auth::id(), 403);
        $users = User::where('status', 'active')->get(['id', 'name', 'email']);
        $workflow->load('steps');
        return view('workflows.edit', compact('workflow', 'users'));
    }

    public function update(Request $request, Workflow $workflow)
    {
        abort_if($workflow->created_by !== Auth::id(), 403);
        $workflow->update($request->only('name', 'description', 'status'));

        if ($request->wantsJson()) {
            $workflow->load(['steps.assignee', 'executions']);
            return response()->json(['workflow' => $this->formatWorkflow($workflow)]);
        }

        return redirect()->route('workflows.show', $workflow)->with('success', 'Workflow mis à jour.');
    }

    public function destroy(Workflow $workflow)
    {
        abort_if($workflow->created_by !== Auth::id(), 403);
        $workflow->delete();

        if (request()->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('workflows.index')->with('success', 'Workflow supprimé.');
    }

    public function duplicate(Request $request, Workflow $workflow)
    {
        $this->ensureWorkflowVisibility($workflow);
        $workflow->load('steps');
        $copy = Workflow::create([
            'id'          => Str::uuid(),
            'name'        => $workflow->name . ' (copie)',
            'description' => $workflow->description,
            'status'      => 'active',
            'created_by'  => Auth::id(),
            'docs_to_sign'   => $workflow->docs_to_sign ?? [],
            'attached_docs'  => $workflow->attached_docs ?? [],
        ]);

        foreach ($workflow->steps as $step) {
            WorkflowStep::create([
                'id'                 => Str::uuid(),
                'workflow_id'        => $copy->id,
                'order'              => $step->order,
                'name'               => $step->name,
                'type'               => $step->type,
                'assignee_id'        => $step->assignee_id,
                'description'        => $step->description,
                'requires_signature' => $step->requires_signature,
            ]);
        }

        $copy->load(['steps.assignee', 'executions']);

        return response()->json(['workflow' => $this->formatWorkflow($copy)], 201);
    }

    // ── Exécutions ───────────────────────────────────────────────────────────

    public function execute(Request $request, Workflow $workflow)
    {
        $this->ensureWorkflowVisibility($workflow);
        $request->validate(['document_id' => 'nullable|exists:documents,id']);

        // Zones de signature par document {docId: [{page,x,y,width,height,label}, ...]}
        $docZones = $request->input('doc_zones', []);

        $docIds = $workflow->docs_to_sign ?? [];
        if (empty($docIds) && $request->document_id) {
            $docIds = [$request->document_id];
        }
        if (empty($docIds)) {
            $docIds = [null];
        }

        $executions = collect($docIds)->map(function ($docId) use ($workflow, $docZones) {
            return WorkflowExecution::create([
                'id'           => Str::uuid(),
                'workflow_id'  => $workflow->id,
                'document_id'  => $docId,
                'status'       => 'in_progress',
                'current_step' => 1,
                'step_data'    => ['doc_zones' => $docZones],
            ]);
        });

        // Créer des SignatureRequests pour la première étape du workflow
        $firstStep = $workflow->steps()->orderBy('order')->first();
        if ($firstStep && $firstStep->assignee_id && $firstStep->requires_signature) {
            $this->createSignatureRequestsForStep($workflow, $firstStep, $docZones);
        }

        // Notifier l'assigné de la première étape
        if ($firstStep) {
            NotificationService::workflowExecutionStarted($workflow, $firstStep, Auth::user()->name);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'execution' => $executions->first(),
                'executions' => $executions->values(),
            ]);
        }

        return redirect()->route('workflows.show', $workflow)->with('success', 'Workflow lancé.');
    }

    public function advance(Request $request, Workflow $workflow)
    {
        $this->ensureWorkflowVisibility($workflow);
        $execution = $workflow->executions()->where('status', 'in_progress')->first();
        if (!$execution) {
            return response()->json(['error' => 'Aucune exécution en cours'], 404);
        }

        $totalSteps = $workflow->steps()->count();
        $next = $execution->current_step + 1;

        if ($next > $totalSteps) {
            $execution->update(['status' => 'completed', 'current_step' => $totalSteps]);
            // Notifier le créateur du workflow que c'est terminé
            NotificationService::notify(
                recipientId: $workflow->created_by,
                type: 'workflow_assigned',
                title: 'Workflow terminé',
                message: 'Le workflow « ' . $workflow->name . ' » a été complété avec succès.',
                actionUrl: route('workflows.show', $workflow),
                workflowId: $workflow->id,
            );
        } else {
            $execution->update(['current_step' => $next]);
            $nextStep = $workflow->steps()->where('order', $next)->first();
            if ($nextStep && $nextStep->requires_signature) {
                $docZones = $execution->step_data['doc_zones'] ?? [];
                $this->createSignatureRequestsForStep($workflow, $nextStep, is_array($docZones) ? $docZones : []);
            }
            // Notifier l'assigné de la prochaine étape
            NotificationService::workflowStepAdvanced($workflow, $next, Auth::user()->name);
        }

        return response()->json(['execution' => $execution->fresh()]);
    }

    public function reject(Request $request, Workflow $workflow)
    {
        $this->ensureWorkflowVisibility($workflow);
        $execution = $workflow->executions()->where('status', 'in_progress')->first();
        if (!$execution) {
            return response()->json(['error' => 'Aucune exécution en cours'], 404);
        }

        $execution->update(['status' => 'rejected']);

        return response()->json(['execution' => $execution->fresh()]);
    }

    // ── Templates ────────────────────────────────────────────────────────────

    public function indexTemplates()
    {
        $templates = WorkflowTemplate::where('created_by', Auth::id())
            ->latest()->get();

        return response()->json($templates->map(fn($t) => [
            'id'               => $t->id,
            'name'             => $t->name,
            'description'      => $t->description,
            'validation_steps' => $t->validation_steps ?? [],
            'signature_steps'  => $t->signature_steps ?? [],
            'notification_config' => $t->notification_config ?? null,
        ])->values());
    }

    public function storeTemplate(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:500',
        ]);

        $template = WorkflowTemplate::create([
            'id'               => Str::uuid(),
            'name'             => $request->name,
            'description'      => $request->description,
            'validation_steps' => $request->validation_steps ?? [],
            'signature_steps'  => $request->signature_steps ?? [],
            'notification_config' => $request->notification_config ?? null,
            'status'           => 'active',
            'created_by'       => Auth::id(),
        ]);

        return response()->json([
            'template' => [
                'id'               => $template->id,
                'name'             => $template->name,
                'description'      => $template->description,
                'validation_steps' => $template->validation_steps ?? [],
                'signature_steps'  => $template->signature_steps ?? [],
                'notification_config' => $template->notification_config ?? null,
            ]
        ], 201);
    }
}

