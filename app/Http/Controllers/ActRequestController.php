<?php

namespace App\Http\Controllers;

use App\Models\ActRequestSubmission;
use App\Models\UserDirectionAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ActRequestController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $search = $request->get('q', '');
        $status = $request->get('status', '');

        $query = ActRequestSubmission::query()->with(['requestedAct', 'administration']);

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
}
