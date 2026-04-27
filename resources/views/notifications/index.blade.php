@extends('layouts.app')
@section('title', 'Notifications')
@section('page-title', 'Notifications')
@section('content')

<div class="flex items-center justify-between mb-5">
    <p class="text-sm text-gray-500">{{ $notifications->total() }} notification(s)</p>
    @if($notifications->where('is_read', false)->count())
    <form method="POST" action="{{ route('notifications.readAll') }}">
        @csrf
        <button class="text-sm text-indigo-600 hover:text-indigo-800">
            <i class="fas fa-check-double mr-1"></i>Tout marquer comme lu
        </button>
    </form>
    @endif
</div>

<div class="space-y-2">
    @forelse($notifications as $notif)
    <div class="bg-white rounded-xl border {{ $notif->is_read ? 'border-gray-100' : 'border-indigo-200 bg-indigo-50/30' }} p-4 flex items-start gap-4">
        <div class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0
            @if(in_array($notif->type, ['validation'])) bg-green-100 text-green-600
            @elseif(in_array($notif->type, ['signature'])) bg-indigo-100 text-indigo-600
            @elseif(in_array($notif->type, ['workflow', 'workflow_assigned'])) bg-purple-100 text-purple-600
            @elseif($notif->type === 'system') bg-red-100 text-red-600
            @elseif($notif->type === 'document_share') bg-blue-100 text-blue-600
            @elseif($notif->type === 'chat_message') bg-emerald-100 text-emerald-600
            @elseif($notif->type === 'template_share') bg-amber-100 text-amber-600
            @else bg-gray-100 text-gray-500 @endif">
            <i class="fas
                @if($notif->type === 'validation') fa-check-circle
                @elseif($notif->type === 'signature') fa-pen-nib
                @elseif(in_array($notif->type, ['workflow', 'workflow_assigned'])) fa-project-diagram
                @elseif($notif->type === 'system') fa-cog
                @elseif($notif->type === 'document_share') fa-share-alt
                @elseif($notif->type === 'chat_message') fa-comment-dots
                @elseif($notif->type === 'template_share') fa-file-alt
                @else fa-info-circle @endif text-sm"></i>
        </div>
        <div class="flex-1 min-w-0">
            <p class="font-semibold text-gray-800 text-sm">{{ $notif->title }}</p>
            <p class="text-xs text-gray-500 mt-0.5">{{ $notif->message }}</p>
            <p class="text-xs text-gray-400 mt-1">{{ $notif->created_at->diffForHumans() }}</p>
        </div>
        <div class="flex items-center gap-2">
            @if(!$notif->is_read)
            <form method="POST" action="{{ route('notifications.read', $notif) }}">
                @csrf
                <button class="text-xs text-indigo-600 hover:text-indigo-800 whitespace-nowrap">Marquer lu</button>
            </form>
            @else
            <span class="text-xs text-gray-400 flex items-center gap-1"><i class="fas fa-check-double"></i> Lu</span>
            @endif
        </div>
    </div>
    @empty
    <div class="bg-white rounded-xl border border-gray-100 p-12 text-center text-gray-400">
        <i class="fas fa-bell-slash text-5xl mb-4 block text-gray-200"></i>
        <p class="font-medium">Aucune notification</p>
    </div>
    @endforelse
</div>

@if($notifications->hasPages())
<div class="mt-6">{{ $notifications->links() }}</div>
@endif
@endsection
