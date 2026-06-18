<?php

namespace App\Http\Middleware;

use App\Services\AdminAuditLogger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogAdminAudit
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return $response;
        }

        if ($response->getStatusCode() >= 400) {
            return $response;
        }

        if ($request->is('api/admin/audit-logs*')) {
            return $response;
        }

        AdminAuditLogger::logFromRequest($request);

        return $response;
    }
}
