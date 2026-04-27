@extends('layouts.app')
@section('title', $document->title)
@section('page-title', 'Document')
@section('content')

<div class="max-w-4xl mx-auto space-y-6">

    <!-- En-tête document -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-start justify-between">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 bg-red-100 rounded-xl flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-file-pdf text-red-500 text-2xl"></i>
                </div>
                <div>
                    <h1 class="text-xl font-bold text-gray-800">{{ $document->title }}</h1>
                    @if($document->description)
                    <p class="text-sm text-gray-500 mt-1">{{ $document->description }}</p>
                    @endif
                    <div class="flex items-center gap-3 mt-2 flex-wrap">
                        <span class="text-xs px-2.5 py-1 rounded-full font-medium
                            @if($document->status === 'signed') bg-green-100 text-green-700
                            @elseif($document->status === 'draft') bg-gray-100 text-gray-600
                            @elseif($document->status === 'pending_signature') bg-yellow-100 text-yellow-700
                            @elseif($document->status === 'archived') bg-blue-100 text-blue-700
                            @else bg-indigo-100 text-indigo-700 @endif">
                            {{ ['draft'=>'Brouillon','active'=>'Actif','signed'=>'Signé','archived'=>'Archivé','pending_signature'=>'En attente'][$document->status] ?? $document->status }}
                        </span>
                        <span class="text-xs text-gray-400"><i class="fas fa-calendar mr-1"></i>{{ $document->created_at->format('d/m/Y H:i') }}</span>
                        @if($document->file_size)
                        <span class="text-xs text-gray-400"><i class="fas fa-weight mr-1"></i>{{ number_format($document->file_size / 1024, 1) }} Ko</span>
                        @endif
                        <span class="text-xs text-gray-400"><i class="fas fa-user mr-1"></i>{{ $document->owner->name ?? '—' }}</span>
                    </div>
                </div>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('documents.download', $document) }}" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-indigo-700 flex items-center gap-2">
                    <i class="fas fa-download"></i> Télécharger
                </a>
                @if(auth()->id() === $document->owner_id)
                <a href="{{ route('documents.edit', $document) }}" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg text-sm hover:bg-gray-200 flex items-center gap-2">
                    <i class="fas fa-edit"></i> Modifier
                </a>
                @endif
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Signatures -->
        <div class="lg:col-span-2 space-y-4">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                <div class="p-5 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="font-semibold text-gray-800"><i class="fas fa-pen-nib mr-2 text-indigo-500"></i>Signatures</h2>
                    @if($document->status !== 'signed')
                    <button onclick="document.getElementById('sigModal').classList.remove('hidden')"
                            class="bg-indigo-600 text-white text-xs px-3 py-1.5 rounded-lg hover:bg-indigo-700">
                        <i class="fas fa-plus mr-1"></i> Signer
                    </button>
                    @endif
                </div>
                @forelse($document->signatures as $sig)
                <div class="p-4 border-b border-gray-50 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center text-xs font-bold text-green-700">
                            {{ strtoupper(substr($sig->signer->name ?? 'U', 0, 2)) }}
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-800">{{ $sig->signer->name ?? 'Inconnu' }}</p>
                            <p class="text-xs text-gray-400">{{ $sig->signed_at?->format('d/m/Y H:i') }}</p>
                        </div>
                    </div>
                    <span class="text-xs px-2 py-1 rounded-full bg-green-100 text-green-700"><i class="fas fa-check mr-1"></i>Valide</span>
                </div>
                @empty
                <div class="p-8 text-center text-gray-400">
                    <i class="fas fa-pen-nib text-3xl mb-2 block text-gray-200"></i>
                    Aucune signature
                </div>
                @endforelse
            </div>

            <!-- Versions -->
            @if($document->versions->count())
            <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                <div class="p-5 border-b border-gray-100">
                    <h2 class="font-semibold text-gray-800"><i class="fas fa-history mr-2 text-indigo-500"></i>Versions</h2>
                </div>
                @foreach($document->versions->sortByDesc('version_number') as $v)
                <div class="p-4 border-b border-gray-50 flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-800">Version {{ $v->version_number }}</p>
                        <p class="text-xs text-gray-400">{{ $v->created_at->format('d/m/Y H:i') }}</p>
                    </div>
                    <span class="text-xs text-gray-500">{{ number_format($v->file_size / 1024, 1) }} Ko</span>
                </div>
                @endforeach
            </div>
            @endif
        </div>

        <!-- Infos -->
        <div class="space-y-4">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <h3 class="font-semibold text-gray-800 mb-4 text-sm">Informations</h3>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Type</span>
                        <span class="font-medium">{{ strtoupper(pathinfo($document->file_path, PATHINFO_EXTENSION)) }}</span>
                    </div>
                    @if($document->document_number)
                    <div class="flex justify-between">
                        <span class="text-gray-500">N° document</span>
                        <span class="font-medium font-mono text-xs">{{ $document->document_number }}</span>
                    </div>
                    @endif
                    @if($document->issuingAdministration)
                    <div class="flex justify-between">
                        <span class="text-gray-500">Administration</span>
                        <span class="font-medium">{{ $document->issuingAdministration->name }}</span>
                    </div>
                    @endif
                    <div class="flex justify-between">
                        <span class="text-gray-500">Signatures</span>
                        <span class="font-medium">{{ $document->signatures->count() }}</span>
                    </div>
                </div>
            </div>

            <!-- QR Code -->
            @if($document->qrCodes->count())
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 text-center">
                <h3 class="font-semibold text-gray-800 mb-3 text-sm">QR Code de vérification</h3>
                <p class="text-xs font-mono text-gray-600 bg-gray-50 p-2 rounded">{{ $document->qrCodes->first()->verification_code }}</p>
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Modal signature -->
<div id="sigModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl p-6 w-full max-w-md shadow-xl">
        <h3 class="font-semibold text-gray-800 mb-4">Signer le document</h3>
        <form method="POST" action="{{ route('signatures.store') }}">
            @csrf
            <input type="hidden" name="document_id" value="{{ $document->id }}">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Raison (optionnel)</label>
                <input type="text" name="reason" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Motif de signature...">
            </div>
            <div class="flex gap-3">
                <button type="submit" class="flex-1 bg-indigo-600 text-white py-2.5 rounded-lg text-sm font-medium hover:bg-indigo-700">
                    <i class="fas fa-pen-nib mr-2"></i>Confirmer la signature
                </button>
                <button type="button" onclick="document.getElementById('sigModal').classList.add('hidden')"
                        class="bg-gray-100 text-gray-700 px-4 py-2.5 rounded-lg text-sm hover:bg-gray-200">
                    Annuler
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
