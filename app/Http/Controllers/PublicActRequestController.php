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
            'attachment_labels' => 'nullable|array',
            'attachment_labels.*' => 'nullable|string|max:255',
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
        $extraFiles = (array) $request->file('attachments_files', []);
        $attachmentLabels = (array) $request->input('attachment_labels', []);
        $requiredLookup = [];
        foreach ($requiredDocs as $docLabel) {
            $docLabel = trim((string) $docLabel);
            if ($docLabel !== '') {
                $requiredLookup[$docLabel] = false;
            }
        }

        foreach ($extraFiles as $idx => $file) {
            if (!$file) {
                continue;
            }

            $selectedLabel = trim((string) ($attachmentLabels[$idx] ?? ''));
            if ($selectedLabel === '') {
                return back()
                    ->withErrors(['attachment_labels.' . $idx => 'Veuillez choisir le nom du fichier dans la liste.'])
                    ->withInput();
            }

            $path = $file->store('act-requests', 'public');
            $isRequiredDocument = array_key_exists($selectedLabel, $requiredLookup);
            if ($isRequiredDocument) {
                $requiredLookup[$selectedLabel] = true;
            }

            $attachments[] = [
                'type' => $isRequiredDocument ? 'required_document' : 'additional',
                'required_label' => $isRequiredDocument ? $selectedLabel : null,
                'uploaded_name' => $selectedLabel,
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
            ];
        }

        foreach ($requiredLookup as $docLabel => $isPresent) {
            if ($isPresent) {
                continue;
            }

            return back()
                ->withErrors(['attachments_files' => 'Le fichier "' . $docLabel . '" est obligatoire.'])
                ->withInput();
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
