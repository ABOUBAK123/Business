<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OllamaService
{
    private string $baseUrl;
    private string $model;
    private int    $timeoutSeconds;

    public function __construct()
    {
        $this->baseUrl        = rtrim(config('services.ollama.url', 'http://localhost:11434'), '/');
        $this->model          = config('services.ollama.model', 'llama3');
        $this->timeoutSeconds = (int) config('services.ollama.timeout', 60);
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function isAvailable(): bool
    {
        try {
            $response = Http::timeout(5)->get($this->baseUrl);
            return $response->successful() || $response->status() === 200;
        } catch (\Throwable) {
            return false;
        }
    }

    public function enrichVariables(array $rawVars): array
    {
        if (empty($rawVars)) {
            return [];
        }
        $varList = array_map(fn ($v) => $v['key'], $rawVars);
        $varJson = json_encode($varList, JSON_UNESCAPED_UNICODE);
        $prompt = "Tu es un assistant specialise dans l'analyse de formulaires administratifs en francais.\n\n"
            . "On t'envoie une liste de noms de variables extraites d'un modele de document officiel.\n"
            . "Pour chaque variable, retourne un objet JSON avec ces champs :\n"
            . "- \"key\": exactement le nom de variable original (inchange)\n"
            . "- \"label\": libelle clair en francais (ex: \"nom_signataire\" -> \"Nom du signataire\")\n"
            . "- \"field_type\": l'un des types suivants uniquement -> text | date | number | select | textarea\n"
            . "- \"required\": true si la variable semble obligatoire, false sinon\n"
            . "- \"placeholder\": un exemple de valeur en francais\n\n"
            . "Variables a analyser (JSON) : " . $varJson . "\n\n"
            . "IMPORTANT : Reponds UNIQUEMENT avec un tableau JSON valide, sans texte ni explication.\n"
            . "Exemple: [{\"key\":\"nom_signataire\",\"label\":\"Nom du signataire\",\"field_type\":\"text\",\"required\":true,\"placeholder\":\"Jean Dupont\"}]";
        try {
            $response = Http::timeout($this->timeoutSeconds)
                ->post("{$this->baseUrl}/api/generate", [
                    'model'   => $this->model,
                    'prompt'  => $prompt,
                    'stream'  => false,
                    'options' => ['temperature' => 0.1, 'top_p' => 0.9],
                ]);
            if (! $response->successful()) {
                Log::warning('OllamaService: HTTP error', ['status' => $response->status()]);
                return $this->fallbackEnrich($rawVars);
            }
            $responseText = $response->json('response', '');
            return $this->parseOllamaResponse($responseText, $rawVars);
        } catch (\Throwable $e) {
            Log::error('OllamaService: exception', ['message' => $e->getMessage()]);
            return $this->fallbackEnrich($rawVars);
        }
    }

    private function parseOllamaResponse(string $text, array $rawVars): array
    {
        if (preg_match('/\[.*\]/s', $text, $m)) {
            $json = $m[0];
        } else {
            Log::warning('OllamaService: no JSON array found in response', ['text' => substr($text, 0, 500)]);
            return $this->fallbackEnrich($rawVars);
        }
        $parsed = json_decode($json, true);
        if (! is_array($parsed)) {
            Log::warning('OllamaService: JSON decode failed');
            return $this->fallbackEnrich($rawVars);
        }
        $enrichedByKey = [];
        foreach ($parsed as $item) {
            if (isset($item['key'])) {
                $enrichedByKey[$item['key']] = $item;
            }
        }
        $validTypes = ['text', 'date', 'number', 'select', 'textarea'];
        $result = [];
        foreach ($rawVars as $raw) {
            $key      = $raw['key'];
            $enriched = $enrichedByKey[$key] ?? null;
            $fieldType = in_array($enriched['field_type'] ?? '', $validTypes, true)
                ? $enriched['field_type'] : $this->guessFieldType($key);
            $result[] = [
                'key'         => $key,
                'label'       => $enriched['label']       ?? $this->humanizeKey($key),
                'field_type'  => $fieldType,
                'required'    => (bool) ($enriched['required'] ?? false),
                'placeholder' => $enriched['placeholder']  ?? '',
            ];
        }
        return $result;
    }

    public function fallbackEnrich(array $rawVars): array
    {
        return array_map(function ($v) {
            $key = $v['key'];
            return [
                'key'         => $key,
                'label'       => $this->humanizeKey($key),
                'field_type'  => $this->guessFieldType($key),
                'required'    => $this->guessRequired($key),
                'placeholder' => $this->guessPlaceholder($key),
            ];
        }, $rawVars);
    }

    private function guessFieldType(string $key): string
    {
        if (preg_match('/date|jour|annee|mois|naissance|signature|delivr/i', $key)) return 'date';
        if (preg_match('/montant|somme|nombre|numero|code_postal|cin|matricule|prix|salaire|indice/i', $key)) return 'number';
        if (preg_match('/motif|objet|description|observation|commentaire|note|detail|resume/i', $key)) return 'textarea';
        if (preg_match('/genre|civilite|sexe|statut|etat|titre/i', $key)) return 'select';
        return 'text';
    }

    private function guessRequired(string $key): bool
    {
        return (bool) preg_match('/nom|prenom|date|signature|objet|titre|nom_complet|cin|matricule/i', $key);
    }

    private function guessPlaceholder(string $key): string
    {
        $map = [
            'nom' => 'Dupont', 'prenom' => 'Jean', 'nom_complet' => 'Jean Dupont',
            'date' => '01/01/2024', 'date_signature' => '01/01/2024', 'date_naissance' => '15/03/1985',
            'montant' => '10 000', 'objet' => 'Objet de la demande',
            'adresse' => '123 Rue de la Republique', 'email' => 'exemple@domaine.dz',
            'telephone' => '0555 123 456', 'matricule' => '12345678',
        ];
        return $map[$key] ?? '';
    }

    private function humanizeKey(string $key): string
    {
        return ucwords(str_replace(['_', '-'], ' ', $key));
    }
}