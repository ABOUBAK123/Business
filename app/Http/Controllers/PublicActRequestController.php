<?php

namespace App\Http\Controllers;

use App\Models\ActRequestSubmission;
use App\Models\IssuingAdministration;
use App\Models\RecipientAdministration;
use App\Models\RequestedAct;
use App\Models\SubEntity;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PublicActRequestController extends Controller
{
    private function generateTrackingNumber(): string
    {
        $prefix = 'DACT-' . now()->format('Ym') . '-';

        for ($i = 0; $i < 25; $i++) {
            $candidate = $prefix . random_int(100000, 999999);
            $exists = ActRequestSubmission::query()
                ->where('tracking_number', $candidate)
                ->exists();

            if (!$exists) {
                return $candidate;
            }
        }

        return $prefix . strtoupper(Str::random(8));
    }

    private function generateTrackingToken(): string
    {
        for ($i = 0; $i < 25; $i++) {
            $candidate = strtolower(Str::random(48));
            $exists = ActRequestSubmission::query()
                ->where('tracking_token', $candidate)
                ->exists();

            if (!$exists) {
                return $candidate;
            }
        }

        return strtolower((string) Str::uuid());
    }

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

        $recipients = RecipientAdministration::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'metadata']);

        return view('public-act-requests.create', compact('administration', 'requestedAct', 'directions', 'recipients'));
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
            'applicant_full_name'        => 'required|string|max:255',
            'applicant_email'            => 'required|email|max:255',
            'applicant_phone'            => 'nullable|string|max:50',
            'direction_code'             => 'nullable|string|max:100',
            'recipient_administration_id'=> 'nullable|exists:recipient_administrations,id',
            'motif'                      => 'nullable|string|max:2000',
            'note'                       => 'nullable|string|max:2000',
            'extra'                      => 'nullable|array',
            'attachments_files'          => 'nullable|array',
            'attachments_files.*'        => 'nullable|file|max:10240',
            'attachment_labels'          => 'nullable|array',
            'attachment_labels.*'        => 'nullable|string|max:255',
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
        $trackingNumber = $this->generateTrackingNumber();
        $trackingToken = $this->generateTrackingToken();

        $submission = ActRequestSubmission::create([
            'tracking_number'             => $trackingNumber,
            'tracking_token'              => $trackingToken,
            'requested_act_id'            => $requestedAct->id,
            'emitter_administration_id'   => $administration->id,
            'direction_code'              => $directionCode,
            'recipient_administration_id' => $request->input('recipient_administration_id') ?: null,
            'motif'                       => trim((string) $request->input('motif', '')),
            'requested_document_name'     => (string) $requestedAct->document_name,
            'applicant_full_name'         => (string) $request->input('applicant_full_name'),
            'applicant_email'             => (string) $request->input('applicant_email', ''),
            'applicant_phone'             => (string) $request->input('applicant_phone', ''),
            'applicant_payload'           => array_merge($extraPayload, [
                '_note' => trim((string) $request->input('note', '')),
            ]),
            'attachments'                 => $attachments,
            'status'                      => 'pending',
        ]);

        return redirect()
            ->route('public.act-requests.create', [$administration->id, $requestedAct->id])
            ->with('success', 'Votre demande a ete enregistree avec succes.')
            ->with('tracking_number', $submission->tracking_number)
            ->with('tracking_url', route('public.act-requests.track', $submission->tracking_token));
    }

    public function track(string $tracking_token)
    {
        $submission = ActRequestSubmission::query()
            ->with(['administration'])
            ->where('tracking_token', $tracking_token)
            ->firstOrFail();

        return view('public-act-requests.track', compact('submission'));
    }
}
