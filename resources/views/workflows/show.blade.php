@extends('layouts.app')
@section('title', $workflow->name)
@section('page-title', 'Workflow')
@section('content')

<div class="max-w-4xl mx-auto space-y-6">

    <!-- En-tête -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-start justify-between">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center">
                    <i class="fas fa-project-diagram text-purple-600 text-xl"></i>
                </div>
                <div>
                    <h1 class="text-xl font-bold text-gray-800">{{ $workflow->name }}</h1>
                    @if($workflow->description)<p class="text-sm text-gray-500 mt-0.5">{{ $workflow->description }}</p>@endif
                    <div class="flex gap-3 mt-1">
                        <span class="text-xs text-gray-400">Par {{ $workflow->creator->name ?? '—' }}</span>
                        <span class="text-xs text-gray-400">{{ $workflow->created_at->format('d/m/Y') }}</span>
                        <span class="text-xs px-2.5 py-0.5 rounded-full font-medium
                            @if($workflow->status === 'active') bg-green-100 text-green-700
                            @else bg-gray-100 text-gray-600 @endif">
                            {{ ucfirst($workflow->status) }}
                        </span>
                    </div>
                </div>
            </div>
            @if(auth()->id() === $workflow->created_by)
            <div class="flex gap-2">
                <a href="{{ route('workflows.edit', $workflow) }}" class="bg-gray-100 text-gray-700 px-3 py-2 rounded-lg text-sm hover:bg-gray-200">
                    <i class="fas fa-edit mr-1"></i> Modifier
                </a>
                <form method="POST" action="{{ route('workflows.execute', $workflow) }}">
                    @csrf
                    <button class="bg-indigo-600 text-white px-3 py-2 rounded-lg text-sm hover:bg-indigo-700">
                        <i class="fas fa-play mr-1"></i> Exécuter
                    </button>
                </form>
            </div>
            @endif
        </div>
    </div>

    <!-- Étapes -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h2 class="font-semibold text-gray-800 mb-5"><i class="fas fa-list-ol mr-2 text-indigo-500"></i>Étapes ({{ $workflow->steps->count() }})</h2>
        @if($workflow->steps->count())
        <div class="relative">
            <div class="absolute left-5 top-5 bottom-5 w-0.5 bg-gray-200"></div>
            <div class="space-y-4">
                @foreach($workflow->steps as $step)
                <div class="flex gap-4 relative">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0 z-10
                        @if($step->type === 'sign') bg-indigo-100 text-indigo-700
                        @elseif($step->type === 'approve') bg-green-100 text-green-700
                        @elseif($step->type === 'reject') bg-red-100 text-red-700
                        @elseif($step->type === 'notify') bg-blue-100 text-blue-700
                        @else bg-gray-100 text-gray-700 @endif">
                        <i class="fas
                            @if($step->type === 'sign') fa-pen-nib
                            @elseif($step->type === 'approve') fa-check
                            @elseif($step->type === 'reject') fa-times
                            @elseif($step->type === 'notify') fa-bell
                            @else fa-eye @endif text-sm"></i>
                    </div>
                    <div class="flex-1 bg-gray-50 rounded-lg p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="font-medium text-gray-800 text-sm">
                                    {{ $step->name ?: ['review'=>'Révision','sign'=>'Signature','approve'=>'Approbation','reject'=>'Rejet','notify'=>'Notification'][$step->type] ?? $step->type }}
                                </p>
                                <p class="text-xs text-gray-500 mt-0.5">
                                    Type : {{ ucfirst($step->type) }}
                                    @if($step->assignee) · Assigné à : <strong>{{ $step->assignee->name }}</strong>@endif
                                </p>
                            </div>
                            <span class="text-xs text-gray-400 font-mono">Étape {{ $step->order }}</span>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @else
        <p class="text-center text-gray-400 py-6">Aucune étape définie</p>
        @endif
    </div>

    <!-- Exécutions -->
    @if($workflow->executions->count())
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-5 border-b border-gray-100">
            <h2 class="font-semibold text-gray-800"><i class="fas fa-history mr-2 text-indigo-500"></i>Exécutions ({{ $workflow->executions->count() }})</h2>
        </div>
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left text-xs font-semibold text-gray-500 uppercase px-5 py-3">Document</th>
                    <th class="text-left text-xs font-semibold text-gray-500 uppercase px-5 py-3">Statut</th>
                    <th class="text-left text-xs font-semibold text-gray-500 uppercase px-5 py-3">Étape courante</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($workflow->executions as $exec)
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3 text-sm text-gray-800">{{ $exec->document->title ?? '—' }}</td>
                    <td class="px-5 py-3">
                        <span class="text-xs px-2 py-1 rounded-full
                            @if($exec->status === 'completed') bg-green-100 text-green-700
                            @elseif($exec->status === 'in_progress') bg-blue-100 text-blue-700
                            @elseif($exec->status === 'rejected') bg-red-100 text-red-700
                            @else bg-gray-100 text-gray-600 @endif">
                            {{ ['in_progress'=>'En cours','completed'=>'Terminé','rejected'=>'Rejeté','paused'=>'Pausé','cancelled'=>'Annulé'][$exec->status] ?? $exec->status }}
                        </span>
                    </td>
                    <td class="px-5 py-3 text-sm text-gray-500">Étape {{ $exec->current_step ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>
@endsection
