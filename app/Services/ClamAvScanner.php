<?php

namespace App\Services;

use App\Models\ClamAvScanLog;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ClamAvScanner
{
    /**
     * Scanne un fichier uploadé via ClamAV et enregistre le résultat.
     * Lance une ValidationException si le fichier est infecté ou si le démon est en erreur.
     * Ne fait rien si CLAMAV_ENABLED=false (dev local).
     *
     * @param  string  $context  Module d'origine (ex: 'documents', 'courriers', 'rh-employes')
     */
    public static function scan(UploadedFile $file, string $context = ''): void
    {
        if (! config('services.clamav.enabled', false)) {
            return;
        }

        $tmpPath = $file->getPathname();
        $cmd     = ['clamdscan', '--no-summary', '--fdpass', $tmpPath];
        $result  = self::execCommand($cmd);

        $logData = [
            'id'             => (string) Str::uuid(),
            'file_name'      => $file->getClientOriginalName(),
            'file_size'      => $file->getSize(),
            'mime_type'      => $file->getMimeType(),
            'context'        => $context ?: null,
            'scanned_by'     => Auth::id() ? (string) Auth::id() : null,
            'ip_address'     => request()->ip(),
            'scanner_output' => mb_substr($result['output'], 0, 2000),
        ];

        if ($result['code'] === 0) {
            ClamAvScanLog::create(array_merge($logData, [
                'result' => 'clean',
                'threat' => null,
            ]));
            return;
        }

        if ($result['code'] === 1) {
            $threat = self::extractThreatName($result['output']);
            ClamAvScanLog::create(array_merge($logData, [
                'result' => 'infected',
                'threat' => $threat,
            ]));
            Log::warning('ClamAV: fichier infecté rejeté', [
                'file'    => $file->getClientOriginalName(),
                'context' => $context,
                'threat'  => $threat,
            ]);
            throw ValidationException::withMessages([
                'file' => 'Le fichier a été rejeté car il contient un contenu malveillant détecté par l\'antivirus.',
            ]);
        }

        // code === 2 : erreur du démon
        ClamAvScanLog::create(array_merge($logData, [
            'result' => 'error',
            'threat' => null,
        ]));
        Log::error('ClamAV: erreur de scan', [
            'file'    => $file->getClientOriginalName(),
            'context' => $context,
            'output'  => $result['output'],
        ]);
        throw ValidationException::withMessages([
            'file' => 'Impossible de vérifier la sécurité du fichier. Veuillez réessayer ou contacter l\'administrateur.',
        ]);
    }

    private static function extractThreatName(string $output): ?string
    {
        // Ligne ClamAV: "/tmp/phpXXXXX: Eicar-Signature FOUND"
        if (preg_match('/:\s+(.+)\s+FOUND/i', $output, $m)) {
            return trim($m[1]);
        }
        return null;
    }

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
