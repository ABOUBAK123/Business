@extends('layouts.app')
@section('title', __('navigation.dashboard'))
@section('page-title', __('navigation.dashboard'))
@section('content')

@php
    $user = auth()->user();
    $docCount   = \App\Models\Document::where('owner_id', $user->id)->count();
    $wfCount    = \App\Models\Workflow::where('created_by', $user->id)->count();
    $sigCount   = \App\Models\Signature::where('signer_id', $user->id)->count();
    $pendingSig = \App\Models\SignatureRequest::where('requested_to', $user->id)->where('status', 'pending')->count();
@endphp

<!-- Stats Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
    <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
        <div class="flex items-center justify-between mb-3">
            <span class="text-sm text-gray-500 font-medium">{{ __('messages.my_documents') }}</span>
            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-file-alt text-blue-600"></i>
            </div>
        </div>
        <div class="text-3xl font-bold text-gray-800">{{ $docCount }}</div>
        <a href="{{ route('documents.index') }}" class="text-xs text-blue-600 hover:underline mt-1 block">{{ __('messages.see_all') }} →</a>
    </div>

    <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
        <div class="flex items-center justify-between mb-3">
            <span class="text-sm text-gray-500 font-medium">{{ __('messages.workflows') }}</span>
            <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-project-diagram text-purple-600"></i>
            </div>
        </div>
        <div class="text-3xl font-bold text-gray-800">{{ $wfCount }}</div>
        <a href="{{ route('workflows.index') }}" class="text-xs text-purple-600 hover:underline mt-1 block">{{ __('messages.see_all') }} →</a>
    </div>

    <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
        <div class="flex items-center justify-between mb-3">
            <span class="text-sm text-gray-500 font-medium">{{ __('messages.signatures') }}</span>
            <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-pen-nib text-green-600"></i>
            </div>
        </div>
        <div class="text-3xl font-bold text-gray-800">{{ $sigCount }}</div>
        <a href="{{ route('signatures.index') }}" class="text-xs text-green-600 hover:underline mt-1 block">{{ __('messages.see_all') }} →</a>
    </div>

    <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
        <div class="flex items-center justify-between mb-3">
            <span class="text-sm text-gray-500 font-medium">{{ __('messages.pending_signatures') }}</span>
            <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-clock text-orange-600"></i>
            </div>
        </div>
        <div class="text-3xl font-bold text-gray-800">{{ $pendingSig }}</div>
        <a href="{{ route('signatures.index') }}" class="text-xs text-orange-600 hover:underline mt-1 block">{{ __('messages.process') }} →</a>
    </div>
</div>

<!-- Recent Documents -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="flex items-center justify-between p-5 border-b border-gray-100">
            <h2 class="font-semibold text-gray-800">{{ __('messages.recent_documents') }}</h2>
            <a href="{{ route('documents.create') }}" class="text-sm bg-indigo-600 text-white px-3 py-1.5 rounded-lg hover:bg-indigo-700">
                <i class="fas fa-plus mr-1"></i> {{ __('messages.new') }}
            </a>
        </div>
        <div class="divide-y divide-gray-50">
            @forelse(\App\Models\Document::where('owner_id', $user->id)->latest()->take(5)->get() as $doc)
            <div class="flex items-center gap-3 p-4">
                <div class="w-9 h-9 bg-red-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-file-pdf text-red-500 text-sm"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-800 truncate">{{ $doc->title }}</p>
                    <p class="text-xs text-gray-400">{{ $doc->created_at->diffForHumans() }}</p>
                </div>
                @php
                    $dClass = match($doc->status) {
                        'signed'            => 'bg-green-100 text-green-700',
                        'approved'          => 'bg-emerald-100 text-emerald-700',
                        'completed'         => 'bg-teal-100 text-teal-700',
                        'sent'              => 'bg-blue-100 text-blue-700',
                        'active'            => 'bg-indigo-100 text-indigo-700',
                        'pending_signature' => 'bg-amber-100 text-amber-700',
                        'processing'        => 'bg-orange-100 text-orange-700',
                        'draft'             => 'bg-gray-100 text-gray-600',
                        'archived'          => 'bg-slate-100 text-slate-600',
                        'rejected'          => 'bg-red-100 text-red-700',
                        default             => 'bg-yellow-100 text-yellow-700',
                    };
                    $dKey = 'documents.status_' . $doc->status;
                    $dLabel = __($dKey) !== $dKey ? __($dKey) : ucfirst(str_replace('_', ' ', $doc->status));
                @endphp
                <span class="text-xs px-2 py-1 rounded-full {{ $dClass }}">{{ $dLabel }}</span>
            </div>
            @empty
            <div class="p-8 text-center text-gray-400 text-sm">
                <i class="fas fa-folder-open text-3xl mb-2 block"></i>
                {{ __('messages.no_documents') }}
            </div>
            @endforelse
        </div>
    </div>

    <!-- Notifications récentes -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="flex items-center justify-between p-5 border-b border-gray-100">
            <h2 class="font-semibold text-gray-800">{{ __('messages.recent_notifications') }}</h2>
            <a href="{{ route('notifications.index') }}" class="text-sm text-indigo-600 hover:underline">{{ __('messages.see_all') }}</a>
        </div>
        <div class="divide-y divide-gray-50">
            @forelse(\App\Models\Notification::where('recipient_id', $user->id)->latest('created_at')->take(5)->get() as $notif)
            <div class="flex items-start gap-3 p-4 {{ !$notif->is_read ? 'bg-indigo-50' : '' }}">
                <div class="w-2 h-2 rounded-full mt-2 flex-shrink-0 {{ !$notif->is_read ? 'bg-indigo-500' : 'bg-gray-300' }}"></div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-800">{{ $notif->title }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">{{ Str::limit($notif->message, 60) }}</p>
                    <p class="text-xs text-gray-400 mt-1">{{ $notif->created_at->diffForHumans() }}</p>
                </div>
            </div>
            @empty
            <div class="p-8 text-center text-gray-400 text-sm">
                <i class="fas fa-bell-slash text-3xl mb-2 block"></i>
                {{ __('messages.no_notifications') }}
            </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
