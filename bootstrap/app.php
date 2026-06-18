<?php

use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\IsAdmin;
use App\Http\Middleware\LogAudit;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'api',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => IsAdmin::class,
            'active' => EnsureUserIsActive::class,
            'admin.audit' => LogAudit::class,
            'optional.sanctum' => \App\Http\Middleware\OptionalSanctumAuth::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
