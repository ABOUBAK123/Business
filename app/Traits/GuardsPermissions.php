<?php

namespace App\Traits;

use App\Services\UserPermissionsService;
use Illuminate\Support\Facades\Auth;

trait GuardsPermissions
{
    protected function canPermission(string $key): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }
        return app(UserPermissionsService::class)->can($user, $key);
    }

    protected function guardPermission(string $key): void
    {
        abort_if(!$this->canPermission($key), 403, 'Accès refusé.');
    }
}
