<?php

namespace App\Http\Controllers;

use App\Models\MeetingRoom;
use App\Services\ClamAvScanner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class MeetingRoomController extends Controller
{
    private function abortIfRoomOutsideAdministration(MeetingRoom $room): void
    {
        $administrationId = $this->resolveAdministrationId();

        if (!$administrationId || (string) $room->administration_id !== (string) $administrationId) {
            abort(403, 'Acces non autorise pour cette salle.');
        }
    }

    private function resolveAdministrationId(): ?string
    {
        $user = Auth::user();
        if (!$user) {
            return null;
        }

        $assignment = DB::table('user_direction_assignments')
            ->where('user_id', (string) $user->id)
            ->orderByDesc('created_at')
            ->first();

        return ($assignment && !empty($assignment->direction_scope_id))
            ? (string) $assignment->direction_scope_id
            : null;
    }

    public function index()
    {
        if (!Schema::hasTable('meeting_rooms') || !Schema::hasTable('user_direction_assignments')) {
            return redirect()->route('dashboard')
                ->with('error', 'Le module Reunions n\'est pas encore initialise sur ce serveur. Lancez les migrations.');
        }

        $administrationId = $this->resolveAdministrationId();

        $rooms = MeetingRoom::when($administrationId, fn ($q) => $q->where('administration_id', $administrationId))
            ->latest()
            ->paginate(15);

        return view('meetings.rooms.index', compact('rooms'));
    }

    public function store(Request $request)
    {
        if (!Schema::hasTable('meeting_rooms') || !Schema::hasTable('user_direction_assignments')) {
            return redirect()->route('dashboard')
                ->with('error', 'Le module Reunions n\'est pas encore initialise sur ce serveur. Lancez les migrations.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'capacity' => 'required|integer|min:1',
            'location' => 'required|string|max:255',
            'equipments' => 'nullable|array',
            'equipments.*' => 'string|max:100',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
            'maintenance_status' => 'required|in:operational,maintenance,out_of_service',
            'photo' => 'nullable|image|max:5120',
        ]);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            ClamAvScanner::scan($request->file('photo'));
            $photoPath = '/storage/' . $request->file('photo')->store('meetings/rooms', 'public');
        }

        MeetingRoom::create([
            'administration_id' => $this->resolveAdministrationId(),
            'name' => $validated['name'],
            'capacity' => $validated['capacity'],
            'location' => $validated['location'],
            'equipments' => $validated['equipments'] ?? [],
            'description' => $validated['description'] ?? null,
            'photo_path' => $photoPath,
            'status' => $validated['status'],
            'maintenance_status' => $validated['maintenance_status'],
        ]);

        return back()->with('success', 'Salle créée avec succès.');
    }

    public function update(Request $request, MeetingRoom $room)
    {
        if (!Schema::hasTable('meeting_rooms') || !Schema::hasTable('user_direction_assignments')) {
            return redirect()->route('dashboard')
                ->with('error', 'Le module Reunions n\'est pas encore initialise sur ce serveur. Lancez les migrations.');
        }

        $this->abortIfRoomOutsideAdministration($room);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'capacity' => 'required|integer|min:1',
            'location' => 'required|string|max:255',
            'equipments' => 'nullable|array',
            'equipments.*' => 'string|max:100',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
            'maintenance_status' => 'required|in:operational,maintenance,out_of_service',
            'photo' => 'nullable|image|max:5120',
        ]);

        $photoPath = $room->photo_path;
        if ($request->hasFile('photo')) {
            if (!empty($photoPath) && str_starts_with($photoPath, '/storage/')) {
                $oldRelativePath = ltrim(substr($photoPath, strlen('/storage/')), '/');
                if (Storage::disk('public')->exists($oldRelativePath)) {
                    Storage::disk('public')->delete($oldRelativePath);
                }
            }

            ClamAvScanner::scan($request->file('photo'));
            $photoPath = '/storage/' . $request->file('photo')->store('meetings/rooms', 'public');
        }

        $room->update([
            'name' => $validated['name'],
            'capacity' => $validated['capacity'],
            'location' => $validated['location'],
            'equipments' => $validated['equipments'] ?? [],
            'description' => $validated['description'] ?? null,
            'photo_path' => $photoPath,
            'status' => $validated['status'],
            'maintenance_status' => $validated['maintenance_status'],
        ]);

        return back()->with('success', 'Salle modifiee avec succes.');
    }

    public function destroy(MeetingRoom $room)
    {
        if (!Schema::hasTable('meeting_rooms') || !Schema::hasTable('user_direction_assignments')) {
            return redirect()->route('dashboard')
                ->with('error', 'Le module Reunions n\'est pas encore initialise sur ce serveur. Lancez les migrations.');
        }

        $this->abortIfRoomOutsideAdministration($room);

        if ($room->meetings()->exists()) {
            return back()->with('error', 'Impossible de supprimer cette salle: des reunions y sont deja rattachees.');
        }

        if (!empty($room->photo_path) && str_starts_with($room->photo_path, '/storage/')) {
            $relativePath = ltrim(substr($room->photo_path, strlen('/storage/')), '/');
            if (Storage::disk('public')->exists($relativePath)) {
                Storage::disk('public')->delete($relativePath);
            }
        }

        $room->delete();

        return back()->with('success', 'Salle supprimee avec succes.');
    }
}
