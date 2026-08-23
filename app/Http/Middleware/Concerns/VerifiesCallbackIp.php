<?php

namespace App\Http\Middleware\Concerns;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Shared IP-allowlist logic for inbound payment provider webhooks/callbacks.
 * Concrete middleware classes provide the provider-specific config prefix and
 * audit log message/channel names via the abstract accessors below.
 */
trait VerifiesCallbackIp
{
    abstract protected function configPrefix(): string;

    abstract protected function auditMessagePrefix(): string;

    protected function ipFilterEnabled(): bool
    {
        return filter_var(
            config($this->configPrefix() . '.ip_filter_enabled', false),
            FILTER_VALIDATE_BOOL
        ) === true;
    }

    protected function resolveClientIp(Request $request): array
    {
        $remoteIp = (string) ($request->server('REMOTE_ADDR') ?? $request->ip() ?? '');
        $trustedProxies = $this->parseRanges(config($this->configPrefix() . '.trusted_proxies', ''));
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

    protected function parseRanges(array|string|null $value): array
    {
        if (is_array($value)) {
            $ranges = $value;
        } else {
            $ranges = explode(',', (string) $value);
        }

        $ranges = array_map(static fn ($range) => trim((string) $range), $ranges);
        return array_values(array_filter($ranges, static fn (string $range) => $range !== ''));
    }

    protected function ipMatchesAny(string $ip, array $ranges): bool
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

    protected function ipMatchesRange(string $ip, string $range): bool
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

    protected function auditInfo(string $suffix, array $context = []): void
    {
        $this->auditLog('info', $suffix, $context);
    }

    protected function auditWarning(string $suffix, array $context = []): void
    {
        $this->auditLog('warning', $suffix, $context);
    }

    private function auditLog(string $level, string $suffix, array $context): void
    {
        $message = $this->auditMessagePrefix() . '.' . $suffix;
        $channel = (string) config($this->configPrefix() . '.audit_log_channel', 'stack');
        $channels = config('logging.channels', []);

        if (is_array($channels) && array_key_exists($channel, $channels)) {
            Log::channel($channel)->{$level}($message, $context);
            return;
        }

        Log::{$level}($message, $context);
    }
}
