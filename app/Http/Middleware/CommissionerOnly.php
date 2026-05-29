<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CommissionerOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->isCommissioner()) {
            abort(403, 'Accès réservé aux commissionnaires.');
        }

        return $next($request);
    }
}
