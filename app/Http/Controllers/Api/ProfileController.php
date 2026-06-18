<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\DeleteAccountRequest;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Services\AccountDeletionService;
use App\Support\PasswordRules;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->json([
            'data' => new UserResource($request->user()),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'prenom' => ['sometimes', 'string', 'max:100'],
            'nom' => ['sometimes', 'string', 'max:100'],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('utilisateurs', 'email')->ignore($user->id)],
            'current_password' => ['required_with:password', 'string'],
            'password' => PasswordRules::optional(),
        ]);

        if (isset($validated['password'])) {
            if (! Hash::check($validated['current_password'], $user->mot_de_passe)) {
                return response()->json(['message' => 'Mot de passe actuel incorrect.'], 422);
            }

            $user->mot_de_passe = Hash::make($validated['password']);
        }

        if (isset($validated['prenom'])) {
            $user->prenom = $validated['prenom'];
        }

        if (isset($validated['nom'])) {
            $user->nom = $validated['nom'];
        }

        if (isset($validated['email'])) {
            $user->email = $validated['email'];
        }

        $user->save();

        return response()->json([
            'data' => new UserResource($user->fresh()),
            'message' => 'Profil mis à jour.',
        ]);
    }

    public function destroy(DeleteAccountRequest $request, AccountDeletionService $accountDeletionService): JsonResponse
    {
        $user = $request->user();

        if (! Hash::check($request->validated('current_password'), $user->mot_de_passe)) {
            return response()->json(['message' => 'Mot de passe actuel incorrect.'], 422);
        }

        try {
            $accountDeletionService->delete($user);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Votre compte a été supprimé. Toutes vos données personnelles ont été effacées ou anonymisées.',
        ]);
    }
}
