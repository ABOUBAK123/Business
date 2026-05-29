<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckTenantActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Super admins and commissioners bypass tenant checks entirely
        if ($user && ($user->is_super_admin || $user->isCommissioner())) {
            return $next($request);
        }

        if (!app()->bound('currentTenant')) {
            return redirect()->route('login');
        }

        $tenant = app('currentTenant');

        if ($tenant->status === 'suspended' || $tenant->status === 'expired') {
            return redirect()->route('subscription.expired');
        }

        return $next($request);
    }
}
