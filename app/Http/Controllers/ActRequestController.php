<?php

namespace App\Http\Controllers;

use App\Models\ActRequestSubmission;
use App\Models\UserDirectionAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class ActRequestController extends Controller
{
    private function scopedRequestsQuery($user)
    {
        $query = ActRequestSubmission::query()->with(['requestedAct', 'administration', 'recipientAdministration']);

        $administrationId = null;
        if ($user && $user->profile) {
            $administrationId = $user->profile->administration_id;
        }

        if ($administrationId) {
            $query->where('emitter_administration_id', $administrationId);

            $assignedDirections = UserDirectionAssignment::query()
                ->where('user_id', $user->id)
                ->where('direction_scope_type', 'emitter')
                ->where('direction_scope_id', $administrationId)
                ->whereNotNull('sub_entity_code')
                ->pluck('sub_entity_code')
                ->map(fn ($code) => trim((string) $code))
                ->filter()
                ->values()
                ->all();

            if (!empty($assignedDirections)) {
                $query->whereIn('direction_code', $assignedDirections);
            }
        }

        return $query;
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $search = $request->get('q', '');
        $status = $request->get('status', '');

        $query = $this->scopedRequestsQuery($user);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('requested_document_name', 'LIKE', "%{$search}%")
                    ->orWhere('applicant_full_name', 'LIKE', "%{$search}%")
                    ->orWhere('applicant_email', 'LIKE', "%{$search}%")
                    ->orWhere('applicant_phone', 'LIKE', "%{$search}%");
            });
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        $requests = $query->latest()->paginate(20);

        return view('act-requests.index', compact('requests', 'search', 'status'));
    }

    public function downloadAttachmentsZip(ActRequestSubmission $submission)
    {
        $user = Auth::user();

        $requestSubmission = $this->scopedRequestsQuery($user)
            ->whereKey($submission->getKey())
            ->firstOrFail();

        $attachments = is_array($requestSubmission->attachments) ? $requestSubmission->attachments : [];
        if (empty($attachments)) {
            return back()->with('error', 'Aucune piece jointe a telecharger.');
        }

        $tempDir = storage_path('app/tmp');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0775, true);
        }

        $zipPath = $tempDir . DIRECTORY_SEPARATOR . uniqid('act_request_' . $requestSubmission->id . '_', true) . '.zip';
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            abort(500, 'Impossible de preparer l archive ZIP.');
        }

        $publicDisk = Storage::disk('public');
        $usedNames = [];
        $addedCount = 0;

        foreach ($attachments as $index => $attachment) {
            $relativePath = '';
            $displayName = '';

            if (is_array($attachment)) {
                $relativePath = ltrim((string) ($attachment['path'] ?? ''), '/');
                $displayName = (string) ($attachment['uploaded_name'] ?? $attachment['original_name'] ?? '');
            } elseif (is_string($attachment)) {
                $relativePath = ltrim($attachment, '/');
            }

            if ($relativePath === '' || !$publicDisk->exists($relativePath)) {
                continue;
            }

            $extension = strtolower(pathinfo($relativePath, PATHINFO_EXTENSION));
            if ($extension === '') {
                $extension = strtolower(pathinfo($displayName, PATHINFO_EXTENSION));
            }

            $base = trim((string) pathinfo($displayName, PATHINFO_FILENAME));
            if ($base === '') {
                $base = 'piece-jointe-' . ($index + 1);
            }

            $safeBase = (string) Str::of($base)
                ->ascii()
                ->lower()
                ->replaceMatches('/[^a-z0-9]+/', '-')
                ->trim('-');

            if ($safeBase === '') {
                $safeBase = 'piece-jointe-' . ($index + 1);
            }

            $zipEntryName = $safeBase . ($extension !== '' ? ('.' . $extension) : '');
            $suffix = 1;
            while (isset($usedNames[$zipEntryName])) {
                $zipEntryName = $safeBase . '-' . $suffix . ($extension !== '' ? ('.' . $extension) : '');
                $suffix++;
            }

            $usedNames[$zipEntryName] = true;
            $zip->addFromString($zipEntryName, $publicDisk->get($relativePath));
            $addedCount++;
        }

        $zip->close();

        if ($addedCount === 0) {
            @unlink($zipPath);
            return back()->with('error', 'Aucun fichier disponible pour cette demande.');
        }

        if ($requestSubmission->status === 'pending') {
            $requestSubmission->status = 'in_progress';
            $requestSubmission->save();
        }

        $zipName = 'pieces-jointes-demande-' . $requestSubmission->id . '-' . now()->format('Ymd-His') . '.zip';

        return response()->download($zipPath, $zipName, [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend(true);
    }
}
