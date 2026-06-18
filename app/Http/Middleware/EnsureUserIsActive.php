<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || (int) $user->est_actif !== 1 || $user->bloquer) {
            if ($user?->bloquer) {
                $user->tokens()->delete();
            }

            return response()->json(['message' => 'Compte désactivé.'], 403);
        }

        return $next($request);
    }
}
