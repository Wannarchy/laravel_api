<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
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
            'password' => ['sometimes', 'string', 'min:8', 'confirmed'],
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
}
