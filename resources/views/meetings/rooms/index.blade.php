@extends('layouts.app')
@section('title', 'Salles de réunion')
@section('page-title', 'Salles de réunion')
@section('page-subtitle', 'Gestion des salles et disponibilités')

@section('content')
@include('meetings._nav')
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
                    <th class="text-left px-4 py-3 font-semibold text-gray-700">Actions</th>
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
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                data-room='@json([
                                    "id" => (string) $room->id,
                                    "name" => (string) ($room->name ?? ""),
                                    "capacity" => (string) ($room->capacity ?? ""),
                                    "location" => (string) ($room->location ?? ""),
                                    "description" => (string) ($room->description ?? ""),
                                    "status" => (string) ($room->status ?? "active"),
                                    "maintenance_status" => (string) ($room->maintenance_status ?? "operational"),
                                    "equipments" => (array) ($room->equipments ?? []),
                                ])'
                                class="js-open-edit-room px-2.5 py-1.5 rounded-lg bg-blue-100 text-blue-700 text-xs font-semibold hover:bg-blue-200"
                            >
                                Modifier
                            </button>

                            <form method="POST" action="{{ route('meetings.rooms.destroy', $room) }}" onsubmit="return confirm('Supprimer cette salle ?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="px-2.5 py-1.5 rounded-lg bg-red-100 text-red-700 text-xs font-semibold hover:bg-red-200">Supprimer</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-4 py-8 text-center text-gray-400">Aucune salle enregistrée.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        @if($rooms->hasPages())
        <div class="p-4">{{ $rooms->links() }}</div>
        @endif
    </div>
</div>

<div id="edit-room-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4">
    <div class="bg-white rounded-2xl border border-gray-100 shadow-xl w-full max-w-xl">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-semibold text-gray-800">Modifier la salle</h3>
            <button type="button" class="text-gray-500 hover:text-gray-700" onclick="closeEditRoomModal()">✕</button>
        </div>

        <form id="edit-room-form" method="POST" enctype="multipart/form-data" class="p-5 space-y-3">
            @csrf
            @method('PUT')

            <input id="edit-name" name="name" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Nom">
            <input id="edit-capacity" type="number" name="capacity" required min="1" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Capacité">
            <input id="edit-location" name="location" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Localisation/étage">
            <input id="edit-equipments" name="equipments[]" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Équipements (séparés par virgule)">
            <textarea id="edit-description" name="description" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Description"></textarea>
            <input type="file" name="photo" accept="image/*" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">

            <select id="edit-status" name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>

            <select id="edit-maintenance-status" name="maintenance_status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                <option value="operational">Opérationnelle</option>
                <option value="maintenance">En maintenance</option>
                <option value="out_of_service">Hors service</option>
            </select>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="closeEditRoomModal()" class="px-3 py-2 rounded-lg border border-gray-300 text-sm text-gray-700 hover:bg-gray-50">Annuler</button>
                <button type="submit" class="px-3 py-2 rounded-lg bg-[#2453d6] text-white text-sm font-semibold hover:bg-[#1f47bb]">Enregistrer</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditRoomModal(room) {
    const form = document.getElementById('edit-room-form');
    form.action = `{{ url('/meeting-rooms') }}/${room.id}`;

    document.getElementById('edit-name').value = room.name ?? '';
    document.getElementById('edit-capacity').value = room.capacity ?? '';
    document.getElementById('edit-location').value = room.location ?? '';
    document.getElementById('edit-description').value = room.description ?? '';
    document.getElementById('edit-status').value = room.status ?? 'active';
    document.getElementById('edit-maintenance-status').value = room.maintenance_status ?? 'operational';
    document.getElementById('edit-equipments').value = Array.isArray(room.equipments) ? room.equipments.join(', ') : '';

    document.getElementById('edit-room-modal').classList.remove('hidden');
    document.getElementById('edit-room-modal').classList.add('flex');
}

function closeEditRoomModal() {
    document.getElementById('edit-room-modal').classList.remove('flex');
    document.getElementById('edit-room-modal').classList.add('hidden');
}

document.querySelectorAll('.js-open-edit-room').forEach(function (button) {
    button.addEventListener('click', function () {
        try {
            const room = JSON.parse(this.dataset.room || '{}');
            openEditRoomModal(room);
        } catch (e) {
            alert('Impossible d\'ouvrir le formulaire de modification.');
        }
    });
});

document.getElementById('edit-room-form').addEventListener('submit', function () {
    const equipmentsInput = document.getElementById('edit-equipments');
    const values = (equipmentsInput.value || '')
        .split(',')
        .map(v => v.trim())
        .filter(Boolean);

    equipmentsInput.removeAttribute('name');
    values.forEach(v => {
        const hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = 'equipments[]';
        hidden.value = v;
        this.appendChild(hidden);
    });
});
</script>
@endsection
