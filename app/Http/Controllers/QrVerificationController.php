<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Signature;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class QrVerificationController extends Controller
{
    /**
     * Téléchargement direct d'un document via son token QR.
     */
    public function downloadByToken(string $token)
    {
        $token = trim($token);
        abort_if($token === '', 404);

        $document = Document::where('qr_token', $token)->firstOrFail();

        $path = str_replace('/storage/', '', (string) $document->file_path);
        $ext  = pathinfo((string) $document->file_path, PATHINFO_EXTENSION) ?: 'bin';

        if (!Storage::disk('public')->exists($path)) {
            abort(404, 'Fichier introuvable');
        }

        $safeTitle = preg_replace('/[\/\\\\\x00-\x1f]+/', '-', (string) $document->title);
        $safeTitle = trim((string) $safeTitle, '-') ?: 'document';
        $name = pathinfo($safeTitle, PATHINFO_EXTENSION) === $ext ? $safeTitle : ($safeTitle . '.' . $ext);

        return Storage::disk('public')->download($path, $name);
    }

    public function index()
    {
        // Pré-remplir le token depuis l'URL ?token=...
        return view('qr-verification.index', ['token' => request('token', '')]);
    }

    /**
     * Vérifier un code QR / token de document généré (AJAX)
     */
    public function verify(Request $request)
    {
        $request->validate([
            'token' => 'required|string|max:255',
        ]);

        $token = trim($request->token);

        // 1) Chercher un document dont le qr_token correspond
        $document = Document::with(['owner', 'issuingAdministration'])
            ->where('qr_token', $token)
            ->first();

        if ($document) {
            $downloadUrl = route('qr.download', ['token' => $token]);
            return response()->json([
                'valid'           => true,
                'type'            => 'document',
                'document'        => [
                    'id'              => $document->id,
                    'title'           => $document->title,
                    'document_number' => $document->document_number,
                    'sub_entity_code' => $document->sub_entity_code,
                    'created_at'      => $document->created_at?->format('d/m/Y à H:i'),
                    'owner'           => $document->owner?->name,
                    'administration'  => $document->issuingAdministration?->name,
                ],
                'download_url'    => $downloadUrl,
                'message'         => 'Document authentique — généré par le système.',
            ]);
        }

        // 2) Fallback : chercher une signature QR
        $signature = Signature::with(['document', 'signer'])
            ->where('qr_code_token', $token)
            ->first();

        if ($signature && $signature->document) {
            $downloadUrl = route('qr.download', ['token' => $token]);
            return response()->json([
                'valid'        => (bool) $signature->is_valid,
                'type'         => 'signature',
                'document'     => [
                    'id'              => $signature->document->id,
                    'title'           => $signature->document->title,
                    'document_number' => $signature->document->document_number,
                    'created_at'      => $signature->document->created_at?->format('d/m/Y à H:i'),
                ],
                'signer'       => ['name' => $signature->signer?->name],
                'signed_at'    => $signature->signed_at?->format('d/m/Y à H:i'),
                'status'       => $signature->status,
                'download_url' => $downloadUrl,
                'message'      => $signature->is_valid ? 'Document signé authentique.' : 'Signature invalide.',
            ]);
        }

        return response()->json([
            'valid'   => false,
            'message' => 'Aucun document trouvé pour ce code QR.',
        ], 404);
    }

    /**
     * Vérifier un numéro de document généré et retourner un lien de visualisation.
     */
    public function verifyNumber(Request $request)
    {
        $request->validate([
            'document_number' => 'required|string|max:255',
        ]);

        $input = trim((string) $request->input('document_number', ''));
        $normalizedInput = strtoupper(str_replace(' ', '', $input));

        $document = Document::with(['owner', 'issuingAdministration'])
            ->whereNotNull('document_number')
            ->whereRaw('UPPER(REPLACE(document_number, " ", "")) = ?', [$normalizedInput])
            ->first();

        if (!$document) {
            return response()->json([
                'valid' => false,
                'message' => 'Ce document ne fait pas partie des documents générés.',
            ], 404);
        }

        return response()->json([
            'valid' => true,
            'message' => 'Numéro trouvé.',
            'document' => [
                'id' => $document->id,
                'document_number' => $document->document_number,
                'title' => $document->title,
                'created_at' => $document->created_at?->format('d/m/Y à H:i'),
                'owner' => $document->owner?->name,
                'administration' => $document->issuingAdministration?->name,
            ],
            'preview_url' => route('documents.download', ['document' => $document->id, 'inline' => 1]),
            'editor_url' => route('documents.index', ['open_oo' => $document->id]),
        ]);
    }
}
