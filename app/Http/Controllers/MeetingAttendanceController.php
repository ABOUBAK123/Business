<?php

namespace App\Http\Controllers;

use App\Models\IssuingAdministration;
use App\Models\Meeting;
use App\Models\MeetingAttendance;
use App\Models\MeetingParticipant;
use App\Models\SubEntity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MeetingAttendanceController extends Controller
{
    public function dashboard(Meeting $meeting)
    {
        $this->abortIfMeetingOutsideScope($meeting);

        $meeting->load(['participants.user', 'attendances', 'organizer.directionAssignments']);
        $branding = $this->resolveOrganizerBranding($meeting);

        return view('meetings.dashboard', compact('meeting', 'branding'));
    }

    public function showByToken(string $token)
    {
        $meeting = Meeting::with('organizer.directionAssignments')
            ->where('qr_token', $token)
            ->firstOrFail();

        $branding = $this->resolveOrganizerBranding($meeting);

        if (!$this->isQrWindowValid($meeting)) {
            return view('meetings.attendance-expired', [
                'meeting' => $meeting,
                'branding' => $branding,
            ]);
        }

        return view('meetings.attendance', [
            'meeting' => $meeting,
            'branding' => $branding,
        ]);
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

    public function lookupByToken(Request $request, string $token)
    {
        $meeting = Meeting::where('qr_token', $token)->firstOrFail();

        $identifier = trim($request->input('identifier', ''));
        if ($identifier === '') {
            return response()->json(null);
        }

        // Search in previous attendances for this identifier
        $attendance = MeetingAttendance::where('identifier', $identifier)->latest()->first();
        if ($attendance) {
            return response()->json([
                'full_name'    => $attendance->full_name,
                'email'        => $attendance->email,
                'phone'        => $attendance->phone,
                'job_title'    => $attendance->job_title,
                'organization' => $attendance->organization,
            ]);
        }

        // Search in meeting participants by email
        $participant = MeetingParticipant::where('meeting_id', $meeting->id)
            ->where(function ($q) use ($identifier) {
                $q->where('email', $identifier)
                  ->orWhereHas('user', fn($u) => $u->where('email', $identifier));
            })
            ->with('user')
            ->first();

        if ($participant) {
            return response()->json([
                'full_name'    => $participant->full_name ?: $participant->user?->name,
                'email'        => $participant->email ?: $participant->user?->email,
                'phone'        => null,
                'job_title'    => null,
                'organization' => null,
            ]);
        }

        return response()->json(null);
    }

    public function downloadAttendance(Request $request, Meeting $meeting)
    {
        $this->abortIfMeetingOutsideScope($meeting);

        $format      = in_array($request->input('format'), ['pdf', 'csv']) ? $request->input('format') : 'csv';
        $orientation = in_array($request->input('orientation'), ['portrait', 'landscape']) ? $request->input('orientation') : 'portrait';

        $attendances = $meeting->attendances()->latest('signed_at')->get();
        $baseName    = 'presence_' . Str::slug($meeting->title) . '_' . ($meeting->starts_at?->format('Ymd') ?? date('Ymd'));

        if ($format === 'pdf') {
            $meeting->load(['participants', 'room', 'organizer']);
            $branding = $this->resolveOrganizerBranding($meeting);
            $branding['logo_pdf_src'] = $this->resolvePdfLogoSource($branding['logo_url'] ?? null);
            $signatureSources = [];

            foreach ($attendances as $attendance) {
                $signatureSources[(string) $attendance->id] = $this->resolvePdfImageSource($attendance->signature_path);
            }

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('meetings.attendance_pdf', [
                'meeting'     => $meeting,
                'attendances' => $attendances,
                'branding'    => $branding,
                'signatureSources' => $signatureSources,
            ]);

            $pdf->setOption('isRemoteEnabled', true);
            $pdf->setPaper('a4', $orientation);

            return $pdf->download($baseName . '.pdf');
        }

        // CSV
        $filename = $baseName . '.csv';
        $headers  = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($meeting, $attendances) {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['Réunion', $meeting->title], ';');
            fputcsv($handle, ['Date', $meeting->starts_at?->format('d/m/Y H:i')], ';');
            fputcsv($handle, ['Salle', $meeting->room?->name . ' (' . $meeting->room?->location . ')'], ';');
            fputcsv($handle, ['Organisateur', $meeting->organizer?->name], ';');
            fputcsv($handle, [], ';');
            fputcsv($handle, ['Nom complet', 'Identifiant', 'Email', 'Téléphone', 'Fonction', 'Organisation', 'Heure de signature'], ';');

            foreach ($attendances as $a) {
                $displayIdentifier = filter_var($a->identifier, FILTER_VALIDATE_EMAIL) ? '' : $a->identifier;

                fputcsv($handle, [
                    $a->full_name,
                    $displayIdentifier,
                    $a->email,
                    $a->phone,
                    $a->job_title,
                    $a->organization,
                    $a->signed_at?->format('d/m/Y H:i:s'),
                ], ';');
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function resolvePdfLogoSource(?string $logoUrl): ?string
    {
        return $this->resolvePdfImageSource($logoUrl);
    }

    private function resolvePdfImageSource(?string $imageUrl): ?string
    {
        if (empty($imageUrl)) {
            return null;
        }

        if (str_starts_with($imageUrl, 'data:image/')) {
            return $imageUrl;
        }

        // Common case: logo served from /storage/... symlinked to storage/app/public
        if (str_starts_with($imageUrl, '/storage/')) {
            $relativePath = ltrim(substr($imageUrl, strlen('/storage/')), '/');
            if (Storage::disk('public')->exists($relativePath)) {
                $content = Storage::disk('public')->get($relativePath);
                $mime = Storage::disk('public')->mimeType($relativePath) ?: 'image/png';
                return 'data:' . $mime . ';base64,' . base64_encode($content);
            }
        }

        // Fallback for public relative paths
        if (str_starts_with($imageUrl, '/')) {
            $publicFile = public_path(ltrim($imageUrl, '/'));
            if (is_file($publicFile)) {
                $content = file_get_contents($publicFile);
                if ($content !== false) {
                    $mime = mime_content_type($publicFile) ?: 'image/png';
                    return 'data:' . $mime . ';base64,' . base64_encode($content);
                }
            }
        }

        // Keep original URL as final fallback (works when remote loading is available)
        return $imageUrl;
    }

    private function isQrWindowValid(Meeting $meeting): bool
    {
        if (!$meeting->qr_valid_from || !$meeting->qr_valid_until) {
            return true;
        }

        $now = now();
        return $now->between($meeting->qr_valid_from, $meeting->qr_valid_until);
    }

    private function resolveOrganizerBranding(Meeting $meeting): array
    {
        $logoUrl = null;
        $tutelleEntityName = null;
        $assignment = optional($meeting->organizer)->directionAssignments
            ?->sortByDesc('created_at')
            ?->first();

        if ($assignment) {
            $issuingAdmin = IssuingAdministration::find($assignment->direction_scope_id);

            if ($issuingAdmin && !empty($issuingAdmin->logo)) {
                $rawLogo = (string) $issuingAdmin->logo;
                if (str_starts_with($rawLogo, 'http://') || str_starts_with($rawLogo, 'https://') || str_starts_with($rawLogo, 'data:image/')) {
                    $logoUrl = $rawLogo;
                } else {
                    $normalized = ltrim($rawLogo, '/');

                    // Common legacy values: "storage/..." or "/storage/..."
                    if (str_starts_with($normalized, 'storage/')) {
                        $normalized = ltrim(substr($normalized, strlen('storage/')), '/');
                    }

                    // Common values from older uploads: "public/..."
                    if (str_starts_with($normalized, 'public/')) {
                        $normalized = ltrim(substr($normalized, strlen('public/')), '/');
                    }

                    if (Storage::disk('public')->exists($normalized)) {
                        $logoUrl = url('/storage/' . $normalized);
                    } elseif (str_starts_with($rawLogo, '/')) {
                        // Relative public path fallback
                        $logoUrl = url($rawLogo);
                    } elseif (is_file(public_path($normalized))) {
                        $logoUrl = url('/' . $normalized);
                    }
                }
            }

            if (!empty($assignment->direction_label)) {
                $tutelleEntityName = (string) $assignment->direction_label;
            }

            if (!$tutelleEntityName && !empty($assignment->sub_entity_code)) {
                $subEntity = SubEntity::query()
                    ->where('scope_id', $assignment->direction_scope_id)
                    ->where('code', $assignment->sub_entity_code)
                    ->first();
                if ($subEntity) {
                    $tutelleEntityName = $subEntity->name;
                }
            }

            if (!$tutelleEntityName && !empty($assignment->sub_entity_code)) {
                $tutelleEntityName = (string) $assignment->sub_entity_code;
            }

            if (!$tutelleEntityName && $issuingAdmin) {
                $tutelleEntityName = (string) $issuingAdmin->name;
            }
        }

        return [
            'logo_url' => $logoUrl,
            'tutelle_entity_name' => $tutelleEntityName,
        ];
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
}
