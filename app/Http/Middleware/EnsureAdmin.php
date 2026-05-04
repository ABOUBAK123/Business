<?php

namespace App\Http\Middleware;

use App\Models\AdministrationProfile;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            abort(403, 'Accès non autorisé.');
        }

        $user = auth()->user();

        // Super-admin ou admin scopé : accès direct
        if ($user->role === 'admin') {
            return $next($request);
        }

        // Utilisateurs avec profil ayant des permissions de menu : accès autorisé (scopé par profil)
        if ($user->profile_id) {
            $profile = AdministrationProfile::find($user->profile_id);
            if ($profile) {
                $perms = $profile->permissions['menuPermissions'] ?? [];
                if (!empty($perms)) {
                    return $next($request);
                }
            }
        }

        abort(403, 'Accès réservé aux administrateurs.');
    }
}
