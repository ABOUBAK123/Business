<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\MeetingParticipant;
use App\Models\MeetingRoom;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MeetingController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        $meetings = Meeting::with(['room', 'organizer', 'minutesWriter'])
            ->when($q !== '', function ($query) use ($q) {
                $query->where('title', 'like', "%{$q}%");
            })
            ->latest('starts_at')
            ->paginate(20)
            ->appends(['q' => $q]);

        return view('meetings.index', compact('meetings', 'q'));
    }

    public function create()
    {
        $rooms = MeetingRoom::where('status', 'active')
            ->where('maintenance_status', 'operational')
            ->orderBy('name')
            ->get();

        $users = User::select('id', 'name', 'email')->orderBy('name')->get();

        return view('meetings.create', compact('rooms', 'users'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'meeting_type' => 'required|in:ordinary,extraordinary,management_committee,project,technical,other',
            'meeting_room_id' => 'required|uuid|exists:meeting_rooms,id',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after:starts_at',
            'minutes_writer_id' => 'required|uuid|exists:users,id',
            'agenda' => 'nullable|string',
            'priority' => 'required|in:low,normal,high,urgent',
            'confidentiality' => 'required|in:public,internal,confidential',
            'recurrence_type' => 'nullable|in:none,daily,weekly,monthly,yearly',
            'recurrence_until' => 'nullable|date|after_or_equal:today',
            'participants' => 'nullable|array',
            'participants.*' => 'uuid|exists:users,id',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx|max:20480',
        ]);

        $startsAt = now()->parse($validated['starts_at']);
        $endsAt = now()->parse($validated['ends_at']);

        $overlapExists = Meeting::where('meeting_room_id', $validated['meeting_room_id'])
            ->whereIn('status', ['planned', 'in_progress'])
            ->where(function ($query) use ($startsAt, $endsAt) {
                $query->where('starts_at', '<', $endsAt)
                    ->where('ends_at', '>', $startsAt);
            })
            ->exists();

        if ($overlapExists) {
            return back()->withInput()->with('error', 'La salle est déjà réservée sur ce créneau horaire.');
        }

        $attachments = [];
        foreach ((array) $request->file('attachments', []) as $file) {
            $path = $file->store('meetings/attachments', 'public');
            $attachments[] = [
                'name' => $file->getClientOriginalName(),
                'path' => '/storage/' . $path,
                'mime' => $file->getMimeType(),
                'size' => $file->getSize(),
            ];
        }

        $meeting = Meeting::create([
            'id' => Str::uuid(),
            'title' => $validated['title'],
            'meeting_type' => $validated['meeting_type'],
            'meeting_room_id' => $validated['meeting_room_id'],
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'estimated_duration_minutes' => $startsAt->diffInMinutes($endsAt),
            'organizer_id' => Auth::id(),
            'minutes_writer_id' => $validated['minutes_writer_id'],
            'agenda' => $validated['agenda'] ?? null,
            'attachments' => $attachments,
            'priority' => $validated['priority'],
            'confidentiality' => $validated['confidentiality'],
            'status' => 'planned',
            'recurrence_type' => $validated['recurrence_type'] ?? 'none',
            'recurrence_until' => $validated['recurrence_until'] ?? null,
            'recurrence_exceptions' => [],
            'qr_token' => (string) Str::uuid(),
            'qr_valid_from' => $startsAt,
            'qr_valid_until' => $endsAt,
        ]);

        foreach ((array) ($validated['participants'] ?? []) as $userId) {
            $participantUser = User::find($userId);
            if (!$participantUser) {
                continue;
            }

            MeetingParticipant::updateOrCreate(
                ['meeting_id' => $meeting->id, 'user_id' => $participantUser->id],
                [
                    'email' => $participantUser->email,
                    'full_name' => $participantUser->name,
                    'participant_role' => 'participant',
                    'is_external' => false,
                    'invitation_status' => 'sent',
                ]
            );
        }

        return redirect()->route('meetings.show', $meeting)->with('success', 'Réunion créée avec succès.');
    }

    public function show(Meeting $meeting)
    {
        $meeting->load(['room', 'organizer', 'minutesWriter', 'participants.user', 'attendances']);

        $qrUrl = route('meetings.qr.show', ['token' => $meeting->qr_token]);

        return view('meetings.show', compact('meeting', 'qrUrl'));
    }
}
