<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || (int) $user->is_admin !== 1) {
            return response()->json(['message' => 'Accès refusé. Droits administrateur requis.'], 403);
        }

        return $next($request);
    }
}
