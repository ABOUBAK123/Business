<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentShare;
use App\Models\RecipientAdministration;
use App\Models\UserDirectionAssignment;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ReceptionController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('q', '');
        $user = Auth::user();
        $userId = $user?->id;
        $userEmail = $user?->email;

        $subEntityCodes = collect();
        if (Schema::hasTable('user_direction_assignments')) {
            try {
                $subEntityCodes = UserDirectionAssignment::query()
                    ->where('user_id', $userId)
                    ->whereNotNull('sub_entity_code')
                    ->pluck('sub_entity_code')
                    ->map(fn ($code) => strtoupper(trim((string) $code)))
                    ->filter()
                    ->values();
            } catch (\Throwable $e) {
                Log::warning('Reception fallback: cannot query user_direction_assignments', [
                    'message' => $e->getMessage(),
                ]);
            }
        }

        $recipientAdminIds = collect();
        $profile = $user?->profile;

        // Si l'utilisateur appartient directement à une administration destinataire,
        // son profil a administration_type='recipient' et administration_id = id de la RecipientAdministration
        if ($profile && $profile->administration_type === 'recipient' && $profile->administration_id) {
            $recipientAdminIds = collect([$profile->administration_id]);
        } elseif (Schema::hasTable('recipient_administrations')) {
            // Fallback : chercher par le code de l'administration émettrice (cas emetteur)
            $adminCode = strtoupper(trim((string) ($profile?->administration?->code ?? '')));
            if ($adminCode !== '') {
                try {
                    $recipientAdminIds = RecipientAdministration::query()
                        ->whereRaw('UPPER(code) = ?', [$adminCode])
                        ->pluck('id');
                } catch (\Throwable $e) {
                    Log::warning('Reception fallback: cannot query recipient_administrations', [
                        'message' => $e->getMessage(),
                    ]);
                }
            }
        }

        $sharedDocIds = collect();

        if (!Schema::hasTable('document_shares')) {
            Log::warning('Reception fallback: missing document_shares table');
        } elseif (!Schema::hasColumns('document_shares', ['document_id', 'recipient_name'])) {
            Log::warning('Reception fallback: document_shares table exists but missing required columns');
        } else {
            try {
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
            } catch (\Throwable $e) {
                Log::warning('Reception fallback: cannot query document shares', [
                    'message' => $e->getMessage(),
                ]);
            }
        }

        if (!Schema::hasTable('documents')) {
            Log::warning('Reception fallback: missing documents table');
            $documents = new LengthAwarePaginator([], 0, 20, 1, [
                'path' => $request->url(),
                'query' => $request->query(),
            ]);

            return view('reception.index', compact('documents', 'search'));
        }

        $query = Document::with(['owner', 'issuingAdministration'])
            ->whereIn('id', $sharedDocIds);

        if (Schema::hasColumn('documents', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        if ($search) {
            $query->where('title', 'LIKE', "%{$search}%");
        }

        $documents = $query->latest()->paginate(20);

        // Récupérer les infos du demandeur pour chaque document (le share le plus récent par document)
        $sharesInfo = collect();
        if ($sharedDocIds->isNotEmpty()) {
            try {
                $sharesInfo = DocumentShare::query()
                    ->whereIn('document_id', $sharedDocIds)
                    ->whereNotNull('applicant_full_name')
                    ->orderByDesc('created_at')
                    ->get(['document_id', 'applicant_full_name', 'applicant_phone', 'applicant_email', 'tracking_number'])
                    ->keyBy('document_id');
            } catch (\Throwable $e) {
                Log::warning('Reception: cannot load applicant info', ['message' => $e->getMessage()]);
            }
        }

        return view('reception.index', compact('documents', 'search', 'sharesInfo'));
    }
}
