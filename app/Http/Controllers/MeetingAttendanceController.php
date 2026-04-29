<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\MeetingAttendance;
use App\Models\MeetingParticipant;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MeetingAttendanceController extends Controller
{
    public function dashboard(Meeting $meeting)
    {
        $meeting->load(['participants.user', 'attendances']);

        return view('meetings.dashboard', compact('meeting'));
    }

    public function showByToken(string $token)
    {
        $meeting = Meeting::where('qr_token', $token)->firstOrFail();

        if (!$this->isQrWindowValid($meeting)) {
            return view('meetings.attendance-expired', compact('meeting'));
        }

        return view('meetings.attendance', compact('meeting'));
    }

    public function signByToken(Request $request, string $token)
    {
        $meeting = Meeting::where('qr_token', $token)->firstOrFail();

        if (!$this->isQrWindowValid($meeting)) {
            return back()->with('error', 'Le QR Code n\'est plus valide pour cette réunion.');
        }

        $validated = $request->validate([
            'identifier' => 'required|string|max:255',
            'full_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'job_title' => 'nullable|string|max:255',
            'organization' => 'nullable|string|max:255',
            'signature' => 'nullable|string',
        ]);

        $identifier = trim($validated['identifier']);

        $participant = MeetingParticipant::where('meeting_id', $meeting->id)
            ->where(function ($query) use ($identifier) {
                $query->where('email', $identifier)
                    ->orWhereHas('user', function ($userQuery) use ($identifier) {
                        $userQuery->where('email', $identifier);
                    });
            })
            ->first();

        $name = $participant?->full_name ?: ($validated['full_name'] ?? null);
        if (!$name && $participant?->user) {
            $name = $participant->user->name;
        }

        if (!$name) {
            return back()->withInput()->with('error', 'Nom obligatoire pour un participant externe.');
        }

        $signaturePath = null;
        if (!empty($validated['signature']) && str_starts_with($validated['signature'], 'data:image/')) {
            $raw = explode(',', $validated['signature'], 2)[1] ?? '';
            $binary = base64_decode($raw, true);
            if ($binary !== false) {
                $fileName = 'meetings/signatures/' . Str::uuid() . '.png';
                \Storage::disk('public')->put($fileName, $binary);
                $signaturePath = '/storage/' . $fileName;
            }
        }

        MeetingAttendance::updateOrCreate(
            ['meeting_id' => $meeting->id, 'identifier' => $identifier],
            [
                'meeting_participant_id' => $participant?->id,
                'full_name' => $name,
                'email' => $participant?->email ?: ($validated['email'] ?? null),
                'phone' => $validated['phone'] ?? null,
                'job_title' => $validated['job_title'] ?? null,
                'organization' => $validated['organization'] ?? null,
                'attendance_status' => now()->greaterThan($meeting->starts_at) ? 'present' : 'present',
                'signed_at' => now(),
                'signature_path' => $signaturePath,
            ]
        );

        return back()->with('success', 'Présence enregistrée avec succès.');
    }

    private function isQrWindowValid(Meeting $meeting): bool
    {
        if (!$meeting->qr_valid_from || !$meeting->qr_valid_until) {
            return true;
        }

        $now = now();
        return $now->between($meeting->qr_valid_from, $meeting->qr_valid_until);
    }
}
