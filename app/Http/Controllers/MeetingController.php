<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\MeetingParticipant;
use App\Models\MeetingRoom;
use App\Models\User;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class MeetingController extends Controller
{
    public function index(Request $request)
    {
        if (!$this->isMeetingsModuleReady()) {
            return redirect()->route('dashboard')
                ->with('error', 'Le module Reunions n\'est pas encore initialise sur ce serveur. Lancez les migrations.');
        }

        $q = trim((string) $request->get('q', ''));
        $scope = $this->resolveCurrentUserScope();

        $meetings = Meeting::with(['room', 'organizer', 'minutesWriter'])
            ->when($scope !== null, function ($query) use ($scope) {
                $query->where('issuing_administration_id', $scope['administration_id'])
                    ->where('sub_entity_code', $scope['sub_entity_code']);
            }, function ($query) {
                // Fallback de securite: sans scope, l'utilisateur ne voit que ses reunions.
                $query->where('organizer_id', Auth::id());
            })
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
        if (!$this->isMeetingsModuleReady()) {
            return redirect()->route('dashboard')
                ->with('error', 'Le module Reunions n\'est pas encore initialise sur ce serveur. Lancez les migrations.');
        }

        $scope = $this->resolveCurrentUserScope();
        if ($scope === null) {
            return redirect()->route('meetings.index')
                ->with('error', 'Votre compte n\'est rattaché à aucune entité sous tutelle.');
        }

        $rooms = MeetingRoom::where('status', 'active')
            ->where('maintenance_status', 'operational')
            ->where('administration_id', $scope['administration_id'])
            ->orderBy('name')
            ->get();

        $users = User::query()
            ->select('users.id', 'users.name', 'users.email')
            ->whereExists(function ($query) use ($scope) {
                $query->select(DB::raw(1))
                    ->from('user_direction_assignments as uda')
                    ->whereColumn('uda.user_id', 'users.id')
                    ->where('uda.direction_scope_id', $scope['administration_id'])
                    ->whereRaw("UPPER(COALESCE(uda.sub_entity_code, '')) = ?", [$scope['sub_entity_code']]);
            })
            ->orderBy('users.name')
            ->get();

        return view('meetings.create', compact('rooms', 'users'));
    }

    public function store(Request $request)
    {
        if (!$this->isMeetingsModuleReady()) {
            return redirect()->route('dashboard')
                ->with('error', 'Le module Reunions n\'est pas encore initialise sur ce serveur. Lancez les migrations.');
        }

        $scope = $this->resolveCurrentUserScope();
        if ($scope === null) {
            return back()->withInput()->with('error', 'Impossible de créer une réunion sans entité sous tutelle.');
        }

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

        $allowedUserIds = $this->resolveScopeUserIds($scope);
        if (!in_array((string) $validated['minutes_writer_id'], $allowedUserIds, true)) {
            return back()->withInput()->with('error', 'Le rédacteur doit appartenir à la même entité sous tutelle.');
        }

        $requestedParticipants = array_map('strval', (array) ($validated['participants'] ?? []));
        $forbiddenParticipants = array_diff($requestedParticipants, $allowedUserIds);
        if (!empty($forbiddenParticipants)) {
            return back()->withInput()->with('error', 'Tous les participants doivent appartenir à la même entité sous tutelle.');
        }

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
            'issuing_administration_id' => $scope['administration_id'],
            'sub_entity_code' => $scope['sub_entity_code'],
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
        if (!$this->isMeetingsModuleReady()) {
            return redirect()->route('dashboard')
                ->with('error', 'Le module Reunions n\'est pas encore initialise sur ce serveur. Lancez les migrations.');
        }

        $this->abortIfMeetingOutsideScope($meeting);

        $meeting->load(['room', 'organizer', 'minutesWriter', 'participants.user', 'attendances']);

        $qrUrl = route('meetings.qr.show', ['token' => $meeting->qr_token]);

        $qrImageDataUri = null;
        try {
            $qrResult = Builder::create()
                ->writer(new PngWriter())
                ->data($qrUrl)
                ->size(360)
                ->margin(8)
                ->build();

            $qrImageDataUri = 'data:image/png;base64,' . base64_encode($qrResult->getString());
        } catch (\Throwable $e) {
            // En cas d'erreur du moteur QR, on garde au minimum le lien scannable/copiable.
            $qrImageDataUri = null;
        }

        return view('meetings.show', compact('meeting', 'qrUrl', 'qrImageDataUri'));
    }

    private function abortIfMeetingOutsideScope(Meeting $meeting): void
    {
        $scope = $this->resolveCurrentUserScope();

        if ($scope === null) {
            abort_unless((string) $meeting->organizer_id === (string) Auth::id(), 403);
            return;
        }

        abort_unless(
            (string) ($meeting->issuing_administration_id ?? '') === $scope['administration_id']
            && strtoupper((string) ($meeting->sub_entity_code ?? '')) === $scope['sub_entity_code'],
            403
        );
    }

    private function resolveCurrentUserScope(): ?array
    {
        $user = Auth::user();
        if (!$user) {
            return null;
        }

        $assignment = DB::table('user_direction_assignments')
            ->where('user_id', (string) $user->id)
            ->orderByDesc('created_at')
            ->first();

        if (!$assignment || empty($assignment->direction_scope_id)) {
            return null;
        }

        return [
            'administration_id' => (string) $assignment->direction_scope_id,
            'sub_entity_code' => strtoupper(trim((string) ($assignment->sub_entity_code ?? ''))),
        ];
    }

    private function isMeetingsModuleReady(): bool
    {
        return Schema::hasTable('meetings')
            && Schema::hasTable('meeting_rooms')
            && Schema::hasTable('meeting_participants')
            && Schema::hasTable('meeting_attendances')
            && Schema::hasTable('user_direction_assignments');
    }

    private function resolveScopeUserIds(array $scope): array
    {
        return DB::table('user_direction_assignments')
            ->where('direction_scope_id', $scope['administration_id'])
            ->whereRaw("UPPER(COALESCE(sub_entity_code, '')) = ?", [$scope['sub_entity_code']])
            ->pluck('user_id')
            ->map(fn ($id) => (string) $id)
            ->unique()
            ->values()
            ->all();
    }
}
