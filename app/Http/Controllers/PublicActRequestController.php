<?php

namespace App\Http\Controllers;

use App\Models\ActRequestSubmission;
use App\Models\IssuingAdministration;
use App\Models\RequestedAct;
use App\Models\SubEntity;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PublicActRequestController extends Controller
{
    private function buildDocKey(string $label): string
    {
        return (string) Str::of($label)
            ->ascii()
            ->lower()
            ->replace("'", '_')
            ->replaceMatches('/[^a-z0-9]+/', '_')
            ->trim('_');
    }

    public function index()
    {
        $administrations = IssuingAdministration::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('public-act-requests.index', [
            'administrations' => $administrations,
            'selectedAdministration' => null,
            'acts' => collect(),
            'groupedActs' => collect(),
            'subEntityByCode' => collect(),
        ]);
    }

    public function showActsByAdministration(string $administration_id)
    {
        $administrations = IssuingAdministration::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $selectedAdministration = IssuingAdministration::query()
            ->where('is_active', true)
            ->findOrFail($administration_id);

        $acts = RequestedAct::query()
            ->where('administration_id', $selectedAdministration->id)
            ->where('is_active', true)
            ->orderBy('direction_code')
            ->orderBy('document_name')
            ->get();

        $subEntityByCode = SubEntity::query()
            ->where('scope_type', 'emitter')
            ->where('scope_id', $selectedAdministration->id)
            ->where('is_active', true)
            ->get(['code', 'name'])
            ->mapWithKeys(function ($se) {
                return [(string) $se->code => (string) $se->name];
            });

        $groupedActs = $acts->groupBy(function ($act) use ($subEntityByCode) {
            $code = trim((string) ($act->direction_code ?? ''));
            if ($code === '') {
                return 'Sans entite sous tutelle';
            }
            $label = (string) ($subEntityByCode[$code] ?? 'Entite inconnue');
            return $code . ' - ' . $label;
        });

        return view('public-act-requests.index', compact('administrations', 'selectedAdministration', 'acts', 'groupedActs', 'subEntityByCode'));
    }

    public function create(string $administration_id, string $requested_act_id)
    {
        $administration = IssuingAdministration::query()
            ->where('is_active', true)
            ->findOrFail($administration_id);

        $requestedAct = RequestedAct::query()
            ->where('administration_id', $administration->id)
            ->where('is_active', true)
            ->findOrFail($requested_act_id);

        $directions = SubEntity::query()
            ->where('scope_type', 'emitter')
            ->where('scope_id', $administration->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['code', 'name']);

        return view('public-act-requests.create', compact('administration', 'requestedAct', 'directions'));
    }

    public function store(Request $request, string $administration_id, string $requested_act_id)
    {
        $administration = IssuingAdministration::query()
            ->where('is_active', true)
            ->findOrFail($administration_id);

        $requestedAct = RequestedAct::query()
            ->where('administration_id', $administration->id)
            ->where('is_active', true)
            ->findOrFail($requested_act_id);

        $request->validate([
            'applicant_full_name' => 'required|string|max:255',
            'applicant_email' => 'required|email|max:255',
            'applicant_phone' => 'nullable|string|max:50',
            'direction_code' => 'nullable|string|max:100',
            'note' => 'nullable|string|max:2000',
            'extra' => 'nullable|array',
            'attachments_files' => 'nullable|array',
            'attachments_files.*' => 'nullable|file|max:10240',
            'attachments_names' => 'nullable|array',
            'attachments_names.*' => 'nullable|string|max:255',
            'required_files' => 'nullable|array',
            'required_files.*' => 'nullable|file|max:10240',
            'required_file_names' => 'nullable|array',
            'required_file_names.*' => 'nullable|string|max:255',
        ]);

        $extraPayload = [];
        $requestedFields = is_array($requestedAct->applicant_fields) ? $requestedAct->applicant_fields : [];
        $incomingExtra = $request->input('extra', []);

        foreach ($requestedFields as $field) {
            $label = trim((string) ($field['label'] ?? ''));
            if ($label === '') {
                continue;
            }
            $key = Str::of($label)
                ->ascii()
                ->lower()
                ->replace("'", '_')
                ->replaceMatches('/[^a-z0-9]+/', '_')
                ->trim('_')
                ->toString();
            if ($key === '') {
                continue;
            }
            $extraPayload[$key] = (string) ($incomingExtra[$key] ?? '');
        }

        $attachments = [];

        $requiredDocs = is_array($requestedAct->required_documents) ? $requestedAct->required_documents : [];
        foreach ($requiredDocs as $docLabel) {
            $docLabel = trim((string) $docLabel);
            if ($docLabel === '') {
                continue;
            }
            $docKey = $this->buildDocKey($docLabel);
            if ($docKey === '') {
                continue;
            }

            $docFile = $request->file('required_files.' . $docKey);
            if (!$docFile) {
                return back()
                    ->withErrors(['required_files.' . $docKey => 'Le fichier "' . $docLabel . '" est obligatoire.'])
                    ->withInput();
            }

            $uploadedName = trim((string) $request->input('required_file_names.' . $docKey, ''));
            if ($uploadedName === '') {
                return back()
                    ->withErrors(['required_file_names.' . $docKey => 'Le nom du fichier pour "' . $docLabel . '" est obligatoire.'])
                    ->withInput();
            }

            $path = $docFile->store('act-requests', 'public');
            $attachments[] = [
                'type' => 'required_document',
                'required_label' => $docLabel,
                'uploaded_name' => $uploadedName,
                'path' => $path,
                'original_name' => $docFile->getClientOriginalName(),
                'mime_type' => $docFile->getMimeType(),
                'size' => $docFile->getSize(),
            ];
        }

        $extraFiles = (array) $request->file('attachments_files', []);
        $extraNames = (array) $request->input('attachments_names', []);
        foreach ($extraFiles as $idx => $file) {
            if (!$file) {
                continue;
            }

            $uploadedName = trim((string) ($extraNames[$idx] ?? ''));
            if ($uploadedName === '') {
                return back()
                    ->withErrors(['attachments_names.' . $idx => 'Le nom de la pièce jointe est obligatoire.'])
                    ->withInput();
            }

            $path = $file->store('act-requests', 'public');
            $attachments[] = [
                'type' => 'additional',
                'uploaded_name' => $uploadedName,
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
            ];
        }

        $directionCode = trim((string) ($requestedAct->direction_code ?: $request->input('direction_code', '')));

        ActRequestSubmission::create([
            'requested_act_id' => $requestedAct->id,
            'emitter_administration_id' => $administration->id,
            'direction_code' => $directionCode,
            'requested_document_name' => (string) $requestedAct->document_name,
            'applicant_full_name' => (string) $request->input('applicant_full_name'),
            'applicant_email' => (string) $request->input('applicant_email', ''),
            'applicant_phone' => (string) $request->input('applicant_phone', ''),
            'applicant_payload' => array_merge($extraPayload, [
                '_note' => trim((string) $request->input('note', '')),
            ]),
            'attachments' => $attachments,
            'status' => 'pending',
        ]);

        return redirect()
            ->route('public.act-requests.create', [$administration->id, $requestedAct->id])
            ->with('success', 'Votre demande a ete enregistree avec succes.');
    }
}
