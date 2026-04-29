<?php

namespace App\Http\Controllers;

use App\Models\MeetingRoom;
use Illuminate\Http\Request;

class MeetingRoomController extends Controller
{
    public function index()
    {
        $rooms = MeetingRoom::latest()->paginate(15);

        return view('meetings.rooms.index', compact('rooms'));
    }

    public function store(Request $request)
    {
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
            $photoPath = '/storage/' . $request->file('photo')->store('meetings/rooms', 'public');
        }

        MeetingRoom::create([
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
}
