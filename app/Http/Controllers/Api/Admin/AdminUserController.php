<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Support\PasswordRules;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminUserController extends Controller
{
    public function index(): JsonResponse
    {
        $users = User::orderByDesc('date_inscription')->get();

        return response()->json([
            'data' => UserResource::collection($users),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $user = User::find($id);

        if (! $user) {
            return response()->json(['message' => 'Utilisateur introuvable.'], 404);
        }

        return response()->json([
            'data' => new UserResource($user),
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $user = User::find($id);

        if (! $user) {
            return response()->json(['message' => 'Utilisateur introuvable.'], 404);
        }

        $validated = $request->validate([
            'prenom' => ['sometimes', 'string', 'max:100'],
            'nom' => ['sometimes', 'string', 'max:100'],
            'email' => ['sometimes', 'email', 'max:255', 'unique:utilisateurs,email,'.$id],
            'est_confirme' => ['sometimes', 'boolean'],
            'is_admin' => ['sometimes', 'boolean'],
            'est_actif' => ['sometimes', 'boolean'],
            'bloquer' => ['sometimes', 'boolean'],
            'password' => ['sometimes', 'string', PasswordRules::rule()],
        ]);

        if (isset($validated['password'])) {
            $validated['mot_de_passe'] = Hash::make($validated['password']);
            unset($validated['password']);
        }

        $user->update($validated);

        if (! empty($validated['bloquer'])) {
            $user->tokens()->delete();
        }

        return response()->json([
            'data' => new UserResource($user->fresh()),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $user = User::find($id);

        if (! $user) {
            return response()->json(['message' => 'Utilisateur introuvable.'], 404);
        }

        if ((int) $user->id === (int) auth()->id()) {
            return response()->json(['message' => 'Vous ne pouvez pas supprimer votre propre compte.'], 422);
        }

        $user->tokens()->delete();
        $user->delete();

        return response()->json(['message' => 'Utilisateur supprimé.']);
    }

    public function setBlocked(Request $request, int $id): JsonResponse
    {
        $user = User::find($id);

        if (! $user) {
            return response()->json(['message' => 'Utilisateur introuvable.'], 404);
        }

        if ((bool) $user->is_admin) {
            return response()->json(['message' => 'Impossible de bloquer un administrateur.'], 422);
        }

        if ((int) $user->id === (int) auth()->id()) {
            return response()->json(['message' => 'Vous ne pouvez pas bloquer votre propre compte.'], 422);
        }

        $validated = $request->validate([
            'bloquer' => ['required', 'boolean'],
        ]);

        $user->update(['bloquer' => $validated['bloquer']]);

        if ($validated['bloquer']) {
            $user->tokens()->delete();
        }

        return response()->json([
            'data' => new UserResource($user->fresh()),
            'message' => $validated['bloquer'] ? 'Utilisateur bloqué.' : 'Utilisateur débloqué.',
        ]);
    }
}
