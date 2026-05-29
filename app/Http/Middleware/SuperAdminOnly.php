<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SuperAdminOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user()?->is_super_admin) {
            abort(403, 'Accès réservé aux super administrateurs.');
        }

        return $next($request);
    }
}
