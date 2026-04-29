@extends('layouts.app')
@section('title', 'Salles de réunion')
@section('page-title', 'Salles de réunion')
@section('page-subtitle', 'Gestion des salles et disponibilités')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <form method="POST" action="{{ route('meetings.rooms.store') }}" enctype="multipart/form-data" class="lg:col-span-1 bg-white rounded-2xl border border-gray-100 shadow-sm p-4 space-y-3">
        @csrf
        <h3 class="font-semibold text-gray-800">Nouvelle salle</h3>
        <input name="name" placeholder="Nom" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
        <input type="number" name="capacity" placeholder="Capacité" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
        <input name="location" placeholder="Localisation/étage" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
        <input name="equipments[]" placeholder="Équipement (répétez pour multi)" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
        <textarea name="description" rows="3" placeholder="Description" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"></textarea>
        <input type="file" name="photo" accept="image/*" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
        <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
        </select>
        <select name="maintenance_status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            <option value="operational">Opérationnelle</option>
            <option value="maintenance">En maintenance</option>
            <option value="out_of_service">Hors service</option>
        </select>
        <button class="w-full px-3 py-2 rounded-lg bg-[#2453d6] text-white text-sm font-semibold hover:bg-[#1f47bb]">Créer la salle</button>
    </form>

    <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-100">
                <tr>
                    <th class="text-left px-4 py-3 font-semibold text-gray-700">Nom</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-700">Capacité</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-700">Lieu</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-700">Statut</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-700">Maintenance</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rooms as $room)
                <tr class="border-b border-gray-100">
                    <td class="px-4 py-3 text-gray-800">{{ $room->name }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $room->capacity }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $room->location }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $room->status }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $room->maintenance_status }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-4 py-8 text-center text-gray-400">Aucune salle enregistrée.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        @if($rooms->hasPages())
        <div class="p-4">{{ $rooms->links() }}</div>
        @endif
    </div>
</div>
@endsection
