@extends('layouts.app')
@section('title', 'Signatures')
@section('page-title', 'Signatures')
@section('content')

{{-- ── Flash messages ──────────────────────────────────────────────── --}}
@if(session('success'))
<div id="flash-success" class="mb-5 px-4 py-3 bg-green-50 border border-green-200 text-green-700 rounded-xl text-sm font-medium flex items-center gap-2">
    <i class="fa-solid fa-circle-check text-green-500"></i> {{ session('success') }}
</div>
@endif
@if(session('error'))
<div class="mb-5 px-4 py-3 bg-red-50 border border-red-200 text-red-700 rounded-xl text-sm font-medium flex items-center gap-2">
    <i class="fa-solid fa-circle-xmark text-red-500"></i> {{ session('error') }}
</div>
@endif

@php
    $pendingActionCount = collect($workflowInbox)->filter(function ($row) {
        return (($row['status'] ?? null) !== 'completed') && !empty($row['actionableIds']);
    })->count();
@endphp

{{-- ── Barre d'actions rapides ─────────────────────────────────────── --}}
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 mb-6">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-sm font-semibold text-gray-700">
        <button onclick="scrollToSection('section-inbox')"
            class="flex items-center gap-2 px-4 py-3 rounded-xl hover:bg-amber-50 hover:text-amber-700 transition">
            <i class="fa-solid fa-hourglass-half text-amber-500 w-5 text-center"></i>
            En attente
            <span class="ml-auto bg-amber-500 text-white text-xs font-bold px-2 py-0.5 rounded-full">{{ $pendingActionCount }}</span>
        </button>
        <button onclick="scrollToSection('section-workflows')"
            class="flex items-center gap-2 px-4 py-3 rounded-xl hover:bg-green-50 hover:text-green-700 transition">
            <i class="fa-solid fa-diagram-project text-green-500 w-5 text-center"></i> Workflows
        </button>
        <button onclick="openWorkflowCreateModal()"
            class="flex items-center gap-2 px-4 py-3 rounded-xl bg-[#2453d6] hover:bg-[#1f47bb] text-white transition">
            <i class="fa-solid fa-plus w-5 text-center"></i>
            Creer un workflow de signature
        </button>
    </div>
</div>

{{-- ── Boite de reception workflow ─────────────────────────────────── --}}
<section id="section-inbox" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 mb-6">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2">
            <i class="fa-solid fa-inbox text-[#2453d6]"></i>
            Boite de reception des actions (signature / validation)
        </h2>
        <span class="text-xs text-gray-400 bg-gray-100 px-3 py-1 rounded-full">{{ count($workflowInbox) }} ligne(s)</span>
    </div>
    @if(count($workflowInbox) === 0)
        <p class="text-sm text-gray-400 py-6 text-center">
            <i class="fa-regular fa-folder-open text-3xl mb-2 block text-gray-200"></i>
            Aucun workflow en execution pour signature/validation.
        </p>
    @else
    <div class="overflow-x-auto rounded-xl border border-gray-200">
        <table class="w-full min-w-[760px] text-left text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wide">
                <tr>
                    <th class="px-4 py-3 font-semibold">Workflow</th>
                    <th class="px-4 py-3 font-semibold">Createur</th>
                    <th class="px-4 py-3 font-semibold">Document(s)</th>
                    <th class="px-4 py-3 font-semibold">Progression</th>
                    <th class="px-4 py-3 font-semibold">Statut</th>
                    <th class="px-4 py-3 font-semibold">Prochain intervenant</th>
                    <th class="px-4 py-3 font-semibold text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($workflowInbox as $row)
                <tr class="{{ $row['isMyTurn'] ? 'bg-white' : 'bg-gray-50' }} hover:bg-blue-50/30 transition-colors">
                    <td class="px-4 py-3 font-medium text-gray-800">{{ $row['workflowName'] }}</td>
                    <td class="px-4 py-3 text-gray-600 text-xs">{{ $row['creatorLabel'] }}</td>
                    <td class="px-4 py-3 text-gray-600 text-xs">{{ $row['documentTitle'] }}</td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            <div class="w-20 h-2 rounded-full bg-gray-200 overflow-hidden">
                                <div class="h-2 rounded-full bg-[#2453d6]" style="width: {{ $row['progress'] }}%"></div>
                            </div>
                            <span class="text-xs text-gray-600 font-semibold">{{ $row['progress'] }}%</span>
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        @php
                            $sc = match($row['statusLabel']) {
                                'En cours'  => 'bg-amber-100 text-amber-800',
                                'Termine'   => 'bg-green-100 text-green-800',
                                'Rejete'    => 'bg-red-100 text-red-700',
                                default     => 'bg-blue-100 text-blue-700',
                            };
                        @endphp
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold {{ $sc }}">
                            {{ $row['statusLabel'] }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-gray-500 text-xs">{{ $row['nextActorLabel'] }}</td>
                    <td class="px-4 py-3 text-right">
                        @if($row['status'] !== 'completed' && count($row['actionableIds']) > 0)
                            @php $firstExecId = $row['actionableIds'][0]; @endphp
                            <button
                                type="button"
                                onclick="wfInboxAction(this, '{{ $firstExecId }}', '{{ $row['actionType'] }}')"
                                data-action="{{ $row['actionType'] }}"
                                data-exec="{{ $firstExecId }}"
                                class="inline-flex items-center justify-center h-8 w-8 rounded-lg border {{ $row['actionType'] === 'signature' ? 'border-blue-200 text-blue-600 hover:bg-blue-50' : 'border-emerald-200 text-emerald-600 hover:bg-emerald-50' }} transition"
                                title="{{ $row['actionType'] === 'signature' ? 'Signer sur la plateforme' : 'Valider sur la plateforme' }}">
                                <i class="fa-solid {{ $row['actionType'] === 'signature' ? 'fa-pen-to-square' : 'fa-circle-check' }} text-sm wf-btn-icon"></i>
                            </button>
                            {{-- Formulaire de repli (local) si API non configurée --}}
                            <form id="wf-local-{{ $firstExecId }}" method="POST" action="{{ route('signatures.workflow-action') }}" class="hidden">
                                @csrf
                                @foreach($row['actionableIds'] as $execId)
                                    <input type="hidden" name="execution_ids[]" value="{{ $execId }}">
                                @endforeach
                                <input type="hidden" name="action_type" value="{{ $row['actionType'] }}">
                            </form>
                        @else
                            <span class="text-gray-300 text-xs">—</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</section>

{{-- section-sign et section-request supprimées --}}
<div class="hidden">
    <section id="section-sign">
        <h2 class="text-lg font-bold text-gray-800 mb-5 flex items-center gap-2">
            <i class="fa-solid fa-pen-nib text-[#2453d6]"></i> Signature de document
        </h2>
        <div class="mb-5">
            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Document</label>
            <select id="doc-selector" onchange="syncDocSelect(this.value)"
                class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#2453d6]/30 bg-white">
                <option value="">— Choisir un document —</option>
                @foreach($documents as $doc)
                    <option value="{{ $doc->id }}">{{ $doc->title }}{{ $doc->status === 'signed' ? ' ✓' : '' }}</option>
                @endforeach
            </select>
        </div>
        <div class="border border-gray-100 rounded-xl p-4 mb-4 bg-gray-50">
            <h3 class="font-semibold text-gray-700 mb-3 flex items-center gap-2">
                <i class="fa-solid fa-signature text-blue-500 text-sm"></i> Signer le document selectionne
            </h3>
            <form method="POST" action="{{ route('signatures.store') }}">
                @csrf
                <input type="hidden" name="document_id" id="sign-doc-id">
                <div class="mb-3">
                    <label class="block text-xs font-medium text-gray-500 mb-1">Hash / Empreinte de signature</label>
                    <input type="text" name="signature" required placeholder="Ex: SHA256:a3f2b..."
                        class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#2453d6]/30">
                </div>
                <div class="mb-3">
                    <label class="block text-xs font-medium text-gray-500 mb-1">Raison (optionnel)</label>
                    <input type="text" name="reason" placeholder="Ex: Approbation DRH"
                        class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#2453d6]/30">
                </div>
                <div class="flex items-center gap-3">
                    <button type="submit"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-[#2453d6] hover:bg-[#1f47bb] text-white rounded-xl text-sm font-semibold transition shadow-sm">
                        <i class="fa-solid fa-pen-nib"></i> Signer
                    </button>
                    <a href="{{ route('signatures.upload') }}"
                        class="inline-flex items-center gap-2 px-5 py-2.5 border border-gray-200 text-gray-700 hover:bg-gray-100 rounded-xl text-sm font-semibold transition">
                        <i class="fa-solid fa-upload"></i> Signer depuis mon PC
                    </a>
                </div>
            </form>
        </div>
        <div id="section-request" class="border border-gray-100 rounded-xl p-4 bg-gray-50">
            <h3 class="font-semibold text-gray-700 mb-3 flex items-center gap-2">
                <i class="fa-solid fa-paper-plane text-violet-500 text-sm"></i> Demander une signature a quelqu'un
            </h3>
            <form method="POST" action="{{ route('signatures.request') }}">
                @csrf
                <input type="hidden" name="document_id" id="request-doc-id">
                <div class="mb-3">
                    <label class="block text-xs font-medium text-gray-500 mb-1">Destinataire</label>
                    <select name="requested_to" required
                        class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#2453d6]/30 bg-white">
                        <option value="">— Choisir un utilisateur —</option>
                        @foreach($users as $u)
                            <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="block text-xs font-medium text-gray-500 mb-1">Message (optionnel)</label>
                    <input type="text" name="message" placeholder="Instructions pour le signataire..."
                        class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#2453d6]/30">
                </div>
                <div class="mb-3">
                    <label class="block text-xs font-medium text-gray-500 mb-1">Date d'expiration (optionnel)</label>
                    <input type="date" name="expiry_date"
                        class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#2453d6]/30">
                </div>
                <button type="submit"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-violet-600 hover:bg-violet-700 text-white rounded-xl text-sm font-semibold transition shadow-sm">
                    <i class="fa-solid fa-paper-plane"></i> Envoyer la demande
                </button>
            </form>
        </div>
    </section>

    <section class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
        <h2 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
            <i class="fa-solid fa-shield-halved text-[#2453d6]"></i> Mes Signatures
            <span class="ml-auto text-xs text-gray-400 bg-gray-100 px-2 py-0.5 rounded-full font-normal">{{ $mySignatures->total() }}</span>
        </h2>
        @if($mySignatures->isEmpty())
            <div class="text-center py-10 text-gray-400">
                <i class="fa-solid fa-pen-nib text-4xl mb-3 block text-gray-200"></i>
                <p class="text-sm">Aucune signature effectuee</p>
            </div>
        @else
        <div class="space-y-2 max-h-96 overflow-y-auto pr-1">
            @foreach($mySignatures as $sig)
            <div class="border border-gray-100 rounded-xl p-3 bg-gray-50 hover:bg-white transition">
                <p class="text-sm font-medium text-gray-800 truncate">
                    <i class="fa-solid fa-file-alt text-blue-400 mr-1 text-xs"></i>
                    {{ $sig->document->title ?? 'Document supprime' }}
                </p>
                <div class="flex items-center justify-between mt-1.5">
                    <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full font-semibold
                        {{ $sig->status === 'valid' ? 'bg-green-100 text-green-700' : ($sig->status === 'revoked' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600') }}">
                        <i class="fa-solid fa-{{ $sig->status === 'valid' ? 'check-circle' : 'ban' }} text-[10px]"></i>
                        {{ match($sig->status) { 'valid' => 'Valide', 'revoked' => 'Revoquee', 'expired' => 'Expiree', default => ucfirst($sig->status) } }}
                    </span>
                    <span class="text-xs text-gray-400">{{ $sig->signed_at?->format('d/m/Y') ?? '—' }}</span>
                </div>
            </div>
            @endforeach
        </div>
        @if($mySignatures->hasPages())
            <div class="mt-3 text-center">{{ $mySignatures->links() }}</div>
        @endif
        @endif
    </section>
</div>{{-- fin sections masquées --}}

{{-- ── Suivi global des workflows ──────────────────────────────────── --}}
<section id="section-workflows" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 mb-6">
    <div class="flex items-center justify-between mb-5">
        <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2">
            <i class="fa-solid fa-diagram-project text-[#2453d6]"></i> Suivi global des workflows
        </h2>
        <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-amber-50 border border-amber-200 text-amber-700 text-xs font-semibold">
            <i class="fa-solid fa-clock"></i>
            {{ $pendingActionCount }} en attente validation/signature
        </span>
    </div>
    @if($myWorkflows->isEmpty())
        <div class="text-center py-10 text-gray-400">
            <i class="fa-solid fa-diagram-project text-4xl mb-3 block text-gray-200"></i>
            <p class="text-sm">Aucun workflow cree</p>
        </div>
    @else
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="border-b border-gray-200">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Nom</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Proprietaire</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Derniere modif.</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Statut</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Progression</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($myWorkflows as $wf)
                @php
                    $totalSteps   = $wf->steps->count() ?: 1;
                    $latestExec   = $wf->executions->sortByDesc('current_step')->first();
                    $completedSteps = $latestExec ? min($latestExec->current_step - 1, $totalSteps) : 0;
                    $progress     = (int) round(($completedSteps / $totalSteps) * 100);
                    if ($latestExec && $latestExec->status === 'completed') $progress = 100;
                    $execStatus   = $latestExec?->status ?? null;
                    $statusLabel  = match($execStatus) {
                        'completed'              => 'TERMINE',
                        'rejected'               => 'REJETE',
                        'in_progress', 'pending' => 'DEMARRE',
                        default                  => 'BROUILLON',
                    };
                    $statusColor  = match($execStatus) {
                        'completed'              => 'bg-green-500 text-white',
                        'rejected'               => 'bg-red-500 text-white',
                        'in_progress', 'pending' => 'bg-amber-400 text-white',
                        default                  => 'bg-gray-200 text-gray-700',
                    };
                    $progressColor = ($execStatus === 'completed') ? 'bg-green-500' : 'bg-amber-400';
                    $currentStepObj = $wf->steps->firstWhere('order', $latestExec?->current_step ?? 1);
                    $isSignStep = str_contains(strtolower($currentStepObj?->description ?? $currentStepObj?->name ?? ''), 'signature') || ($currentStepObj?->requires_signature ?? false);
                @endphp
                <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
                    <td class="px-4 py-4 font-medium text-gray-900">{{ $wf->name }}</td>
                    <td class="px-4 py-4">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-full bg-[#2453d6] flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                                {{ strtoupper(substr($wf->creator?->name ?? $wf->creator?->email ?? '?', 0, 1)) }}
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-gray-900 truncate">{{ $wf->creator?->name ?? '—' }}</p>
                                <p class="text-xs text-gray-400 truncate">{{ $wf->creator?->email ?? '' }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-4 text-xs text-gray-500">{{ $wf->updated_at?->format('d/m/y H:i') ?? '—' }}</td>
                    <td class="px-4 py-4">
                        <span class="inline-block px-3 py-1 rounded-full text-xs font-bold uppercase {{ $statusColor }}">{{ $statusLabel }}</span>
                    </td>
                    <td class="px-4 py-4">
                        <div class="w-28 h-7 rounded border border-gray-300 bg-white overflow-hidden relative">
                            <div class="absolute inset-y-0 left-0 {{ $progressColor }} transition-all" style="width: {{ $progress }}%"></div>
                            <span class="absolute inset-0 flex items-center justify-center text-xs font-bold text-gray-700">{{ $progress }} %</span>
                        </div>
                    </td>
                    <td class="px-4 py-4 text-right">
                        @if($isSignStep)
                            <span title="Etape de signature" class="p-1.5 rounded text-blue-400">
                                <i class="fa-solid fa-pen-to-square text-base"></i>
                            </span>
                        @else
                            <span title="Etape de validation" class="p-1.5 rounded text-green-500">
                                <i class="fa-solid fa-circle-check text-base"></i>
                            </span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</section>

{{-- ── Historique complet des signatures ──────────────────────────── --}}
<section class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden mb-6">
    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
        <h2 class="font-bold text-gray-800 text-base flex items-center gap-2">
            <i class="fa-solid fa-list-check text-[#2453d6]"></i> Historique complet des signatures
        </h2>
        <span class="text-xs text-gray-400 bg-gray-100 px-3 py-1 rounded-full">{{ $mySignatures->total() }} signature(s)</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-100">
                <tr>
                    <th class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wide px-5 py-3">Document</th>
                    <th class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wide px-5 py-3">Zone positionnee</th>
                    <th class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wide px-5 py-3">Statut</th>
                    <th class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wide px-5 py-3">Date</th>
                    <th class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wide px-5 py-3">Raison</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($mySignatures as $sig)
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-5 py-4">
                        <p class="text-sm font-medium text-gray-800">{{ $sig->document->title ?? 'Document supprime' }}</p>
                    </td>
                    <td class="px-5 py-4">
                        @php
                            $zoneReq = $sig->document
                                ? \App\Models\SignatureRequest::where('document_id', $sig->document_id)
                                    ->where('requested_to', $sig->signer_id)
                                    ->whereNotNull('zone_x')
                                    ->latest('responded_at')
                                    ->first()
                                : null;
                        @endphp
                        @if($zoneReq)
                            <div class="text-xs text-blue-700 bg-blue-50 border border-blue-200 rounded-lg px-2.5 py-1.5 inline-block">
                                <i class="fa-solid fa-location-dot text-blue-400 mr-1"></i>
                                Page {{ $zoneReq->zone_page }} · X {{ round($zoneReq->zone_x, 1) }}% · Y {{ round($zoneReq->zone_y, 1) }}%
                            </div>
                        @else
                            <span class="text-xs text-gray-300 italic">Non definie</span>
                        @endif
                    </td>
                    <td class="px-5 py-4">
                        <span class="text-xs px-2.5 py-1 rounded-full font-semibold
                            {{ $sig->status === 'valid' ? 'bg-green-100 text-green-700' : ($sig->status === 'revoked' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600') }}">
                            <i class="fa-solid fa-{{ $sig->status === 'valid' ? 'check-circle' : 'ban' }} mr-1 text-xs"></i>
                            {{ match($sig->status) { 'valid' => 'Valide', 'revoked' => 'Revoquee', 'expired' => 'Expiree', default => ucfirst($sig->status) } }}
                        </span>
                    </td>
                    <td class="px-5 py-4 text-xs text-gray-500">{{ $sig->signed_at?->format('d/m/Y H:i') ?? '—' }}</td>
                    <td class="px-5 py-4 text-xs text-gray-500">{{ $sig->reason ?? '—' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-5 py-16 text-center text-gray-400">
                        <i class="fa-solid fa-pen-nib text-5xl mb-4 block text-gray-200"></i>
                        <p class="text-base font-medium text-gray-400">Aucune signature effectuee</p>
                        <p class="text-sm mt-1">Les demandes de signature vous seront envoyees par les gestionnaires.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($mySignatures->hasPages())
        <div class="px-6 py-4 border-t border-gray-100">{{ $mySignatures->links() }}</div>
    @endif
</section>

{{-- ═══ Modal : Creer un workflow de signature ══════════════════════ --}}
<div id="modal-create-wf" class="hidden fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl border border-gray-200 w-full max-w-2xl h-[90vh] overflow-hidden flex flex-col">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                <i class="fa-solid fa-diagram-project text-[#2453d6]"></i>
                Creer un workflow de signature
            </h3>
            <button onclick="closeWorkflowCreateModal()"
                class="h-8 w-8 flex items-center justify-center rounded-lg hover:bg-gray-100 text-gray-500 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="px-6 py-3 border-b border-gray-100 bg-gray-50 text-sm text-gray-500">
            Le formulaire du module Workflows s'ouvre ici sans quitter l'onglet Signatures.
        </div>
        <iframe
            id="workflow-create-frame"
            title="Formulaire de creation de workflow"
            class="w-full flex-1 bg-gray-50"
            loading="lazy"
            src="about:blank"></iframe>
    </div>
</div>

@push('scripts')
<script>
function syncDocSelect(val) {
    document.getElementById('sign-doc-id').value    = val;
    document.getElementById('request-doc-id').value = val;
}
function scrollToSection(id) {
    const el = document.getElementById(id);
    if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
}
const flash = document.getElementById('flash-success');
if (flash) setTimeout(() => { flash.style.transition='opacity .5s'; flash.style.opacity='0'; setTimeout(()=>flash.remove(),500); }, 4000);

const workflowCreateModal = document.getElementById('modal-create-wf');
const workflowCreateFrame = document.getElementById('workflow-create-frame');
const workflowCreateUrl = @json(route('workflows.index', ['create' => 'workflow', 'embedded' => '1']));

function openWorkflowCreateModal() {
    if (workflowCreateFrame && workflowCreateFrame.dataset.loaded !== '1') {
        workflowCreateFrame.src = workflowCreateUrl;
        workflowCreateFrame.dataset.loaded = '1';
    }

    workflowCreateModal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeWorkflowCreateModal() {
    workflowCreateModal.classList.add('hidden');
    document.body.style.overflow = '';
}

workflowCreateModal.addEventListener('click', function (e) {
    if (e.target === workflowCreateModal) {
        closeWorkflowCreateModal();
    }
});

document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && !workflowCreateModal.classList.contains('hidden')) {
        closeWorkflowCreateModal();
    }
});

window.addEventListener('message', function (event) {
    const data = event.data || {};
    if (data.source !== 'wf-embedded') return;

    if (data.type === 'close') {
        closeWorkflowCreateModal();
        return;
    }

    if (data.type === 'workflow-created') {
        closeWorkflowCreateModal();
        window.location.reload();
    }
});

// ── Boîte de réception : Signer / Valider via API plateforme ────────────────
const __wfInviteUrl = "{{ route('signatures.get-invite-url') }}";
const __wfCsrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

async function wfInboxAction(btn, executionId, actionType) {
    const icon = btn.querySelector('.wf-btn-icon');
    const originalClass = icon?.className ?? '';

    // Spinner
    if (icon) {
        icon.className = 'fa-solid fa-spinner fa-spin text-sm wf-btn-icon';
    }
    btn.disabled = true;

    try {
        const resp = await fetch(__wfInviteUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': __wfCsrfToken,
            },
            body: JSON.stringify({ execution_id: executionId, action_type: actionType }),
        });

        let data = null;
        try {
            data = await resp.json();
        } catch (_) {
            data = { ok: false, message: 'Réponse non JSON reçue du serveur (session expirée ou erreur 500).' };
        }

        if (data.ok && data.url) {
            window.open(data.url, '_blank', 'noopener,noreferrer');
        } else {
            const msg = data.message || 'Erreur inconnue.';
            alert('API Signature indisponible: ' + msg + '\n\nAucune action locale automatique n\'a été lancée pour éviter de masquer le problème.');
        }
    } catch (err) {
        console.error('wfInboxAction error:', err);
        alert('Erreur réseau/API Signature: ' + (err?.message || 'inconnue') + '\n\nAucune action locale automatique n\'a été lancée.');
    } finally {
        if (icon) icon.className = originalClass;
        btn.disabled = false;
    }
}
</script>
@endpush
@endsection
