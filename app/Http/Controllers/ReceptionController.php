<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentShare;
use App\Models\RecipientAdministration;
use App\Models\UserDirectionAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReceptionController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('q', '');
        $user = Auth::user();
        $userId = $user?->id;
        $userEmail = $user?->email;

        $subEntityCodes = UserDirectionAssignment::query()
            ->where('user_id', $userId)
            ->whereNotNull('sub_entity_code')
            ->pluck('sub_entity_code')
            ->map(fn ($code) => strtoupper(trim((string) $code)))
            ->filter()
            ->values();

        $recipientAdminIds = collect();
        $adminCode = strtoupper(trim((string) ($user?->profile?->administration?->code ?? '')));
        if ($adminCode !== '') {
            $recipientAdminIds = RecipientAdministration::query()
                ->whereRaw('UPPER(code) = ?', [$adminCode])
                ->pluck('id');
        }

        $sharedDocIds = DocumentShare::query()
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            })
            ->where(function ($q) use ($userId, $userEmail, $subEntityCodes, $recipientAdminIds) {
                $q->where('recipient_name', 'user:' . $userId);

                if (!empty($userEmail)) {
                    $q->orWhere('recipient_email', $userEmail);
                }

                foreach ($subEntityCodes as $code) {
                    $q->orWhere('recipient_name', 'sub_entity:' . $code);
                }

                if ($recipientAdminIds->isNotEmpty()) {
                    $q->orWhereIn('recipient_administration_id', $recipientAdminIds);
                }
            })
            ->pluck('document_id')
            ->unique()
            ->values();

        $query = Document::with(['owner', 'issuingAdministration'])
            ->whereIn('id', $sharedDocIds)
            ->whereNull('deleted_at');

        if ($search) {
            $query->where('title', 'LIKE', "%{$search}%");
        }

        $documents = $query->latest()->paginate(20);

        return view('reception.index', compact('documents', 'search'));
    }
}
