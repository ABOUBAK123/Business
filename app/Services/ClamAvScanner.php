<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class ClamAvScanner
{
    /**
     * Scanne un fichier uploadé via ClamAV.
     * Lance une ValidationException si le fichier est infecté.
     * Ne fait rien si CLAMAV_ENABLED=false (dev local).
     */
    public static function scan(UploadedFile $file): void
    {
        if (! config('services.clamav.enabled', false)) {
            return;
        }

        $socket  = config('services.clamav.socket', '/var/run/clamav/clamd.ctl');
        $tmpPath = $file->getPathname();

        // clamdscan lit le fichier temporaire PHP directement
        $cmd    = ['clamdscan', '--no-summary', '--fdpass', $tmpPath];
        $result = self::execCommand($cmd);

        if ($result['code'] === 1) {
            Log::warning('ClamAV: fichier infecté rejeté', [
                'original_name' => $file->getClientOriginalName(),
                'output'        => $result['output'],
            ]);
            throw ValidationException::withMessages([
                'file' => 'Le fichier a été rejeté car il contient un contenu malveillant détecté par l\'antivirus.',
            ]);
        }

        if ($result['code'] === 2) {
            Log::error('ClamAV: erreur de scan', [
                'original_name' => $file->getClientOriginalName(),
                'output'        => $result['output'],
            ]);
            // En cas d'erreur du démon ClamAV, bloquer par défaut (fail-closed)
            throw ValidationException::withMessages([
                'file' => 'Impossible de vérifier la sécurité du fichier. Veuillez réessayer ou contacter l\'administrateur.',
            ]);
        }
    }

    /**
     * Exécute une commande et retourne son code de sortie + sa sortie standard.
     */
    private static function execCommand(array $cmd): array
    {
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($cmd, $descriptors, $pipes);

        if (! is_resource($process)) {
            return ['code' => 2, 'output' => 'proc_open failed'];
        }

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $code = proc_close($process);

        return ['code' => $code, 'output' => trim($stdout . "\n" . $stderr)];
    }
}
