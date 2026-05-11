<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerifySignaturePlatformWebhook
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('services.signature_platform.webhook_secret');

        // No secret configured: allow but log a warning so the operator knows to set one.
        if (empty($secret)) {
            Log::warning('VerifySignaturePlatformWebhook: SIGNATURE_WEBHOOK_SECRET non configuré — webhook accepté sans vérification.', [
                'ip' => $request->ip(),
            ]);
            return $next($request);
        }

        $token = (string) $request->query('token', '');

        // Constant-time comparison prevents timing attacks.
        if ($token === '' || !hash_equals($secret, $token)) {
            Log::warning('VerifySignaturePlatformWebhook: token invalide ou absent — requête rejetée.', [
                'ip'         => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            return response()->json(['ok' => false, 'message' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
