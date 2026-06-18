<?php

namespace App\Http\Middleware;

use App\Services\AuditLogger;
use Illuminate\Support\Facades\Log;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogAudit
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('api/admin/logs*')) {
            return $next($request);
        }

        $response = $next($request);

        if (! $request->user()) {
            return $response;
        }

        try {
            AuditLogger::logFromRequest($request);
        } catch (\Throwable $e) {
            Log::error('Échec écriture log activité', [
                'path' => $request->path(),
                'method' => $request->method(),
                'error' => $e->getMessage(),
            ]);
        }

        return $response;
    }
}
