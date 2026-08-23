<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerifyMtnCallbackIp
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->ipFilterEnabled()) {
            return $next($request);
        }

        $allowedRanges = $this->parseRanges(config('services.mtn_momo.callback.allowed_ips', ''));
        [$clientIp, $remoteIp, $usesForwardedFor] = $this->resolveClientIp($request);

        if ($allowedRanges === []) {
            $this->auditWarning('mtn.callback.blocked', [
                'reason' => 'allowlist_empty',
                'client_ip' => $clientIp,
                'remote_ip' => $remoteIp,
                'xff' => (string) $request->header('X-Forwarded-For', ''),
                'uses_forwarded_for' => $usesForwardedFor,
                'reference' => $request->header('X-Reference-Id') ?? $request->input('referenceId'),
                'path' => $request->path(),
                'user_agent' => (string) $request->userAgent(),
            ]);

            return response('Forbidden', 403);
        }

        if (! $this->ipMatchesAny($clientIp, $allowedRanges)) {
            $this->auditWarning('mtn.callback.blocked', [
                'reason' => 'ip_not_allowed',
                'client_ip' => $clientIp,
                'remote_ip' => $remoteIp,
                'xff' => (string) $request->header('X-Forwarded-For', ''),
                'uses_forwarded_for' => $usesForwardedFor,
                'reference' => $request->header('X-Reference-Id') ?? $request->input('referenceId'),
                'path' => $request->path(),
                'user_agent' => (string) $request->userAgent(),
            ]);

            return response('Forbidden', 403);
        }

        $this->auditInfo('mtn.callback.accepted', [
            'client_ip' => $clientIp,
            'remote_ip' => $remoteIp,
            'xff' => (string) $request->header('X-Forwarded-For', ''),
            'uses_forwarded_for' => $usesForwardedFor,
            'reference' => $request->header('X-Reference-Id') ?? $request->input('referenceId'),
            'path' => $request->path(),
            'user_agent' => (string) $request->userAgent(),
        ]);

        return $next($request);
    }

    private function ipFilterEnabled(): bool
    {
        return filter_var(
            config('services.mtn_momo.callback.ip_filter_enabled', false),
            FILTER_VALIDATE_BOOL
        ) === true;
    }

    private function resolveClientIp(Request $request): array
    {
        $remoteIp = (string) ($request->server('REMOTE_ADDR') ?? $request->ip() ?? '');
        $trustedProxies = $this->parseRanges(config('services.mtn_momo.callback.trusted_proxies', ''));
        $xff = (string) $request->header('X-Forwarded-For', '');

        if ($xff !== '' && $remoteIp !== '' && $this->ipMatchesAny($remoteIp, $trustedProxies)) {
            $parts = array_map('trim', explode(',', $xff));
            foreach ($parts as $part) {
                if (filter_var($part, FILTER_VALIDATE_IP)) {
                    return [$part, $remoteIp, true];
                }
            }
        }

        return [$remoteIp, $remoteIp, false];
    }

    private function parseRanges(array|string|null $value): array
    {
        if (is_array($value)) {
            $ranges = $value;
        } else {
            $ranges = explode(',', (string) $value);
        }

        $ranges = array_map(static fn ($range) => trim((string) $range), $ranges);
        return array_values(array_filter($ranges, static fn (string $range) => $range !== ''));
    }

    private function ipMatchesAny(string $ip, array $ranges): bool
    {
        if (! filter_var($ip, FILTER_VALIDATE_IP)) {
            return false;
        }

        foreach ($ranges as $range) {
            if ($this->ipMatchesRange($ip, $range)) {
                return true;
            }
        }

        return false;
    }

    private function ipMatchesRange(string $ip, string $range): bool
    {
        if (! str_contains($range, '/')) {
            return $ip === $range;
        }

        [$networkIp, $prefix] = explode('/', $range, 2);
        if (! is_numeric($prefix)) {
            return false;
        }

        $ipBin = @inet_pton($ip);
        $networkBin = @inet_pton($networkIp);

        if ($ipBin === false || $networkBin === false || strlen($ipBin) !== strlen($networkBin)) {
            return false;
        }

        $maxPrefix = strlen($ipBin) * 8;
        $prefixLength = (int) $prefix;
        if ($prefixLength < 0 || $prefixLength > $maxPrefix) {
            return false;
        }

        $fullBytes = intdiv($prefixLength, 8);
        $remainingBits = $prefixLength % 8;

        if ($fullBytes > 0 && substr($ipBin, 0, $fullBytes) !== substr($networkBin, 0, $fullBytes)) {
            return false;
        }

        if ($remainingBits === 0) {
            return true;
        }

        $mask = 0xFF << (8 - $remainingBits);
        $ipByte = ord($ipBin[$fullBytes]);
        $networkByte = ord($networkBin[$fullBytes]);

        return ($ipByte & $mask) === ($networkByte & $mask);
    }

    private function auditInfo(string $message, array $context = []): void
    {
        $channel = (string) config('services.mtn_momo.callback.audit_log_channel', 'mtn_audit');
        $channels = config('logging.channels', []);

        if (is_array($channels) && array_key_exists($channel, $channels)) {
            Log::channel($channel)->info($message, $context);
            return;
        }

        Log::info($message, $context);
    }

    private function auditWarning(string $message, array $context = []): void
    {
        $channel = (string) config('services.mtn_momo.callback.audit_log_channel', 'mtn_audit');
        $channels = config('logging.channels', []);

        if (is_array($channels) && array_key_exists($channel, $channels)) {
            Log::channel($channel)->warning($message, $context);
            return;
        }

        Log::warning($message, $context);
    }
}
