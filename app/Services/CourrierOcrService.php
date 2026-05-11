<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CourrierOcrService
{
    private string $ollamaUrl;
    private string $ollamaModel;
    private int    $timeout;

    public function __construct()
    {
        $this->ollamaUrl   = rtrim(config('services.ollama.url', 'http://localhost:11434'), '/');
        $this->ollamaModel = config('services.ollama.model', 'llama3');
        $this->timeout     = (int) config('services.ollama.timeout', 60);
    }

    /**
     * Extrait les champs d'un courrier depuis un fichier scanné (PDF ou image).
     * Retourne un tableau avec les clés : objet, expediteur, destinataire,
     * date_emission, numero_emission, urgence.
     */
    public function extractFields(UploadedFile $file): array
    {
        $text = $this->extractText($file);

        if (empty(trim($text))) {
            return ['error' => 'Impossible d\'extraire le texte du fichier. Vérifiez que le fichier est lisible.'];
        }

        return $this->extractFieldsWithLlm($text);
    }

    private function extractText(UploadedFile $file): string
    {
        $ext = strtolower($file->getClientOriginalExtension());
        $tmp = $file->getPathname();

        if ($ext === 'pdf') {
            return $this->extractFromPdf($tmp);
        }

        if (in_array($ext, ['jpg', 'jpeg', 'png', 'tiff', 'tif', 'bmp'])) {
            return $this->extractFromImage($tmp);
        }

        return '';
    }

    private function extractFromPdf(string $path): string
    {
        $escaped  = escapeshellarg($path);
        $devNull  = PHP_OS_FAMILY === 'Windows' ? '2>nul' : '2>/dev/null';

        $output = @shell_exec("pdftotext -layout {$escaped} - {$devNull}");
        if (!empty(trim((string) $output))) {
            return (string) $output;
        }

        $output = @shell_exec("gs -dBATCH -dNOPAUSE -sDEVICE=txtwrite -sOutputFile=- {$escaped} {$devNull}");
        return (string) ($output ?? '');
    }

    private function extractFromImage(string $path): string
    {
        $escaped = escapeshellarg($path);
        $devNull = PHP_OS_FAMILY === 'Windows' ? '2>nul' : '2>/dev/null';

        $output = @shell_exec("tesseract {$escaped} stdout -l fra {$devNull}");
        if (!empty(trim((string) $output))) {
            return (string) $output;
        }

        $output = @shell_exec("tesseract {$escaped} stdout {$devNull}");
        return (string) ($output ?? '');
    }

    private function extractFieldsWithLlm(string $text): array
    {
        // Tronquer pour ne pas dépasser le contexte du modèle
        $textTruncated = mb_substr($text, 0, 3000);

        $prompt = <<<PROMPT
Tu es un assistant expert en courriers administratifs francophones.
Analyse ce texte extrait d'un courrier et extrais les informations suivantes au format JSON strict.

Champs à extraire :
- "objet": l'objet ou le sujet principal du courrier (string ou null)
- "expediteur": la personne ou organisation qui envoie le courrier (string ou null)
- "destinataire": la personne ou organisation destinataire (string ou null)
- "date_emission": la date du courrier au format YYYY-MM-DD (string ou null)
- "numero_emission": le numéro de référence, d'enregistrement ou de courrier (string ou null)
- "urgence": "normale", "urgent" ou "tres_urgent" selon les marqueurs d'urgence détectés (string)

Texte du courrier :
{$textTruncated}

Réponds UNIQUEMENT avec un objet JSON valide, sans commentaire ni explication. Exemple :
{"objet":"Demande de congé","expediteur":"Jean Dupont","destinataire":"Direction RH","date_emission":"2026-05-11","numero_emission":"REF-2026-001","urgence":"normale"}
PROMPT;

        try {
            $response = Http::timeout($this->timeout)->post("{$this->ollamaUrl}/api/generate", [
                'model'  => $this->ollamaModel,
                'prompt' => $prompt,
                'stream' => false,
            ]);

            if (!$response->successful()) {
                Log::warning('CourrierOcr: Ollama unavailable', ['status' => $response->status()]);
                return ['error' => 'Le service d\'analyse IA est indisponible. Remplissez les champs manuellement.'];
            }

            $raw = (string) $response->json('response', '');

            // Extraire le premier bloc JSON valide de la réponse
            if (preg_match('/\{[^{}]*\}/s', $raw, $m)) {
                $data = json_decode($m[0], true);
                if (is_array($data)) {
                    return $this->sanitizeFields($data);
                }
            }

            Log::warning('CourrierOcr: no JSON in Ollama response', ['raw' => mb_substr($raw, 0, 300)]);
            return ['error' => 'L\'analyse n\'a pas retourné de résultat structuré. Remplissez les champs manuellement.'];

        } catch (\Throwable $e) {
            Log::error('CourrierOcr: LLM error', ['error' => $e->getMessage()]);
            return ['error' => 'Erreur lors de l\'analyse du document.'];
        }
    }

    private function sanitizeFields(array $data): array
    {
        $valid = ['normale', 'urgent', 'tres_urgent'];

        return [
            'objet'           => isset($data['objet'])           ? mb_substr(trim((string) $data['objet']),           0, 500) : null,
            'expediteur'      => isset($data['expediteur'])      ? mb_substr(trim((string) $data['expediteur']),      0, 300) : null,
            'destinataire'    => isset($data['destinataire'])    ? mb_substr(trim((string) $data['destinataire']),    0, 300) : null,
            'date_emission'   => $this->sanitizeDate($data['date_emission'] ?? null),
            'numero_emission' => isset($data['numero_emission']) ? mb_substr(trim((string) $data['numero_emission']), 0, 100) : null,
            'urgence'         => in_array($data['urgence'] ?? '', $valid) ? $data['urgence'] : 'normale',
        ];
    }

    private function sanitizeDate(mixed $value): ?string
    {
        if (empty($value)) {
            return null;
        }
        $str = trim((string) $value);
        // Accepter uniquement YYYY-MM-DD
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $str)) {
            return $str;
        }
        // Essayer de parser d'autres formats courants
        try {
            $dt = new \DateTime($str);
            return $dt->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }
}
