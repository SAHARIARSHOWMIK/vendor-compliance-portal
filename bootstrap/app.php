<?php

use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\EnsureVendorScopedAccess;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            \App\Http\Middleware\RecordLastLogin::class,
        ]);

        // Named middleware aliases used throughout routes/web.php to gate
        // access by role (see app/Enums/RoleName.php for the 5 roles) and
        // to scope vendor users to their own vendor company's records.
        $middleware->alias([
            'role' => EnsureUserHasRole::class,
            'vendor.scope' => EnsureVendorScopedAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
