<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Signature;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class QrVerificationController extends Controller
{
    private function buildDownloadResponse(Document $document)
    {
        $sourcePath = (string) ($document->signed_file_path ?: $document->file_path ?: '');
        $path = ltrim(str_replace('/storage/', '', $sourcePath), '/');
        $ext  = pathinfo($sourcePath, PATHINFO_EXTENSION) ?: 'bin';

        if ($path === '' || !Storage::disk('public')->exists($path)) {
            abort(404, 'Fichier introuvable');
        }

        $safeTitle = preg_replace('/[\/\\\x00-\x1f]+/', '-', (string) $document->title);
        $safeTitle = trim((string) $safeTitle, '-') ?: 'document';
        $name = pathinfo($safeTitle, PATHINFO_EXTENSION) === $ext ? $safeTitle : ($safeTitle . '.' . $ext);

        return Storage::disk('public')->download($path, $name);
    }

    /**
     * Téléchargement direct d'un document via son token QR.
     */
    public function downloadByToken(string $token)
    {
        $token = trim($token);
        abort_if($token === '', 404);

        $document = Document::where('qr_token', $token)->first();

        if (!$document) {
            $signature = Signature::with('document')->where('qr_code_token', $token)->first();
            $document = $signature?->document;
        }

        abort_if(!$document, 404);

        return $this->buildDownloadResponse($document);
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
            $currentUserId = (string) (auth()->id() ?? '');
            $isOwner = $currentUserId !== '' && (
                (string) $document->owner_id === $currentUserId ||
                (string) ($document->created_by ?? '') === $currentUserId
            );

            $isSigned = !empty($document->signed_file_path);

            // Récupérer les infos du signataire depuis la table signatures si disponible
            $lastSignature = null;
            if ($isSigned) {
                $lastSignature = \App\Models\Signature::with('signer')
                    ->where('document_id', $document->id)
                    ->where('status', 'valid')
                    ->orderByDesc('signed_at')
                    ->first();
            }

            return response()->json([
                'valid'           => true,
                'type'            => 'document',
                'is_signed'       => $isSigned,
                'is_owner'        => $isOwner,
                'editor_url'      => $isOwner ? route('documents.index', ['open_oo' => $document->id]) : null,
                'document'        => [
                    'id'              => $document->id,
                    'title'           => $document->title,
                    'document_number' => $document->document_number,
                    'sub_entity_code' => $document->sub_entity_code,
                    'created_at'      => $document->created_at?->format('d/m/Y à H:i'),
                    'owner'           => $document->owner?->name,
                    'administration'  => $document->issuingAdministration?->name,
                    'signed_at'       => $document->signed_at?->format('d/m/Y à H:i'),
                ],
                'signer'          => $lastSignature ? ['name' => $lastSignature->signer?->name ?? 'Signataire'] : null,
                'signed_at'       => $document->signed_at?->format('d/m/Y à H:i'),
                'download_url'    => $downloadUrl,
                'message'         => $isSigned
                    ? 'Document authentique — version PDF signée électroniquement.'
                    : 'Document authentique — généré par le système (non encore signé).',
            ]);
        }

        // 2) Fallback : chercher une signature QR
        $signature = Signature::with(['document', 'signer'])
            ->where('qr_code_token', $token)
            ->first();

        if ($signature && $signature->document) {
            $downloadUrl = route('qr.download', ['token' => $token]);
            $currentUserId = (string) (auth()->id() ?? '');
            $sigDoc = $signature->document;
            $isOwner = $currentUserId !== '' && (
                (string) $sigDoc->owner_id === $currentUserId ||
                (string) ($sigDoc->created_by ?? '') === $currentUserId
            );
            return response()->json([
                'valid'        => (bool) $signature->is_valid,
                'type'         => 'signature',
                'is_owner'     => $isOwner,
                'editor_url'   => $isOwner ? route('documents.index', ['open_oo' => $sigDoc->id]) : null,
                'document'     => [
                    'id'              => $sigDoc->id,
                    'title'           => $sigDoc->title,
                    'document_number' => $sigDoc->document_number,
                    'created_at'      => $sigDoc->created_at?->format('d/m/Y à H:i'),
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

        $downloadUrl = $document->qr_token
            ? route('qr.download', ['token' => $document->qr_token])
            : null;

        $currentUserId = (string) (auth()->id() ?? '');
        $isOwner = $currentUserId !== '' && (
            (string) $document->owner_id === $currentUserId ||
            (string) ($document->created_by ?? '') === $currentUserId
        );

        return response()->json([
            'valid' => true,
            'message' => 'Numéro trouvé.',
            'is_owner' => $isOwner,
            'document' => [
                'id' => $document->id,
                'document_number' => $document->document_number,
                'title' => $document->title,
                'created_at' => $document->created_at?->format('d/m/Y à H:i'),
                'owner' => $document->owner?->name,
                'administration' => $document->issuingAdministration?->name,
            ],
            'download_url' => $downloadUrl,
            'preview_url' => $isOwner ? route('documents.download', ['document' => $document->id, 'inline' => 1]) : null,
            'editor_url' => $isOwner ? route('documents.index', ['open_oo' => $document->id]) : null,
        ]);
    }
}
