@extends('layouts.app')
@section('title', 'Nouvelle réunion')
@section('page-title', 'Nouvelle réunion')
@section('page-subtitle', 'Planifier une réunion')

@section('content')
@include('meetings._nav')
<form method="POST" action="{{ route('meetings.store') }}" enctype="multipart/form-data" class="space-y-5">
    @csrf

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="md:col-span-2">
            <label class="block text-xs font-semibold text-gray-700 mb-1">Titre</label>
            <input type="text" name="title" value="{{ old('title') }}" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
        </div>

        <div>
            <label class="block text-xs font-semibold text-gray-700 mb-1">Type</label>
            <select name="meeting_type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                <option value="ordinary">Réunion ordinaire</option>
                <option value="extraordinary">Réunion extraordinaire</option>
                <option value="management_committee">Comité de direction</option>
                <option value="project">Réunion de projet</option>
                <option value="technical">Réunion technique</option>
                <option value="other">Autre</option>
            </select>
        </div>

        <div>
            <label class="block text-xs font-semibold text-gray-700 mb-1">Salle</label>
            <select name="meeting_room_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                <option value="">Sélectionner</option>
                @foreach($rooms as $room)
                <option value="{{ $room->id }}">{{ $room->name }} ({{ $room->location }})</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-xs font-semibold text-gray-700 mb-1">Début</label>
            <input type="datetime-local" name="starts_at" value="{{ old('starts_at') }}" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
        </div>

        <div>
            <label class="block text-xs font-semibold text-gray-700 mb-1">Fin</label>
            <input type="datetime-local" name="ends_at" value="{{ old('ends_at') }}" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
        </div>

        <div>
            <label class="block text-xs font-semibold text-gray-700 mb-1">Rédacteur du compte rendu</label>
            <select name="minutes_writer_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                <option value="">Sélectionner</option>
                @foreach($users as $u)
                <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }})</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-xs font-semibold text-gray-700 mb-1">Priorité</label>
            <select name="priority" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                <option value="low">Faible</option>
                <option value="normal" selected>Normale</option>
                <option value="high">Élevée</option>
                <option value="urgent">Urgente</option>
            </select>
        </div>

        <div>
            <label class="block text-xs font-semibold text-gray-700 mb-1">Confidentialité</label>
            <select name="confidentiality" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                <option value="public">Publique</option>
                <option value="internal" selected>Interne</option>
                <option value="confidential">Confidentielle</option>
            </select>
        </div>

        <div>
            <label class="block text-xs font-semibold text-gray-700 mb-1">Récurrence</label>
            <select name="recurrence_type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                <option value="none">Aucune</option>
                <option value="daily">Quotidienne</option>
                <option value="weekly">Hebdomadaire</option>
                <option value="monthly">Mensuelle</option>
                <option value="yearly">Annuelle</option>
            </select>
        </div>

        <div>
            <label class="block text-xs font-semibold text-gray-700 mb-1">Fin de récurrence</label>
            <input type="date" name="recurrence_until" value="{{ old('recurrence_until') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
        </div>

        <div class="md:col-span-2">
            <label class="block text-xs font-semibold text-gray-700 mb-1">Ordre du jour</label>
            <textarea name="agenda" rows="5" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">{{ old('agenda') }}</textarea>
        </div>

        <div class="md:col-span-2">
            <label class="block text-xs font-semibold text-gray-700 mb-1">Participants internes</label>
            <select name="participants[]" multiple class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm h-40">
                @foreach($users as $u)
                <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }})</option>
                @endforeach
            </select>
            <p class="text-xs text-gray-400 mt-1">Maintenez Ctrl (ou Cmd) pour sélectionner plusieurs utilisateurs.</p>
        </div>

        <div class="md:col-span-2">
            <label class="block text-xs font-semibold text-gray-700 mb-1">Documents joints</label>
            <input type="file" name="attachments[]" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
        </div>
    </div>

    <div class="flex justify-end gap-2">
        <a href="{{ route('meetings.index') }}" class="px-4 py-2 rounded-lg border border-gray-300 text-sm text-gray-700 hover:bg-gray-50">Annuler</a>
        <button class="px-4 py-2 rounded-lg bg-[#2453d6] text-white text-sm font-semibold hover:bg-[#1f47bb]">Créer la réunion</button>
    </div>
</form>
@endsection
