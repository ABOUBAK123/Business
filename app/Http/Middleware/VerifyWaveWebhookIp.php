<?php

namespace App\Http\Middleware;

use App\Http\Middleware\Concerns\VerifiesCallbackIp;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyWaveWebhookIp
{
    use VerifiesCallbackIp;

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->ipFilterEnabled()) {
            return $next($request);
        }

        $allowedRanges = $this->parseRanges(config('services.wave.webhook.allowed_ips', ''));
        [$clientIp, $remoteIp, $usesForwardedFor] = $this->resolveClientIp($request);

        if ($allowedRanges === [] || ! $this->ipMatchesAny($clientIp, $allowedRanges)) {
            $this->auditWarning('blocked', [
                'reason' => $allowedRanges === [] ? 'allowlist_empty' : 'ip_not_allowed',
                'client_ip' => $clientIp,
                'remote_ip' => $remoteIp,
                'xff' => (string) $request->header('X-Forwarded-For', ''),
                'uses_forwarded_for' => $usesForwardedFor,
                'path' => $request->path(),
                'user_agent' => (string) $request->userAgent(),
            ]);

            return response('Forbidden', 403);
        }

        $this->auditInfo('accepted', [
            'client_ip' => $clientIp,
            'remote_ip' => $remoteIp,
            'xff' => (string) $request->header('X-Forwarded-For', ''),
            'uses_forwarded_for' => $usesForwardedFor,
            'path' => $request->path(),
            'user_agent' => (string) $request->userAgent(),
        ]);

        return $next($request);
    }

    protected function configPrefix(): string
    {
        return 'services.wave.webhook';
    }

    protected function auditMessagePrefix(): string
    {
        return 'wave.webhook';
    }
}
