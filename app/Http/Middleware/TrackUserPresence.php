<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class TrackUserPresence
{
    private const ONLINE_TTL_SECONDS = 300;

    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $userId = (string) Auth::id();
            Cache::put('chat:online:' . $userId, true, now()->addSeconds(self::ONLINE_TTL_SECONDS));
        }

        return $next($request);
    }
}
