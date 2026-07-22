<?php

use App\Http\Middleware\CheckTenantActive;
use App\Http\Middleware\CommissionerOnly;
use App\Http\Middleware\SetTenantContext;
use App\Http\Middleware\SuperAdminOnly;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            SetTenantContext::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'payment/cinetpay/notify',
        ]);

        $middleware->alias([
            'tenant.active' => CheckTenantActive::class,
            'super.admin' => SuperAdminOnly::class,
            'commissionnaire' => CommissionerOnly::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
