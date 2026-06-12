<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentMethodController extends Controller
{
    public function index(): JsonResponse
    {
        $user = auth()->user();

        if (! $user->hasStripeId()) {
            return response()->json(['data' => []]);
        }

        $methods = collect($user->paymentMethods())->map(function ($method) use ($user) {
            return [
                'id' => $method->id,
                'card_brand' => $method->card->brand ?? null,
                'card_last4' => $method->card->last4 ?? null,
                'exp_month' => $method->card->exp_month ?? null,
                'exp_year' => $method->card->exp_year ?? null,
                'is_default' => $user->defaultPaymentMethod()?->id === $method->id,
            ];
        });

        return response()->json(['data' => $methods]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'payment_method' => ['required', 'string'],
        ]);

        $user = auth()->user();

        $user->createOrGetStripeCustomer([
            'name' => trim($user->prenom.' '.$user->nom),
            'email' => $user->email,
        ]);

        $user->addPaymentMethod($validated['payment_method']);
        $user->updateDefaultPaymentMethod($validated['payment_method']);

        $method = $user->defaultPaymentMethod();

        return response()->json([
            'data' => [
                'id' => $method->id,
                'card_brand' => $method->card->brand ?? null,
                'card_last4' => $method->card->last4 ?? null,
                'exp_month' => $method->card->exp_month ?? null,
                'exp_year' => $method->card->exp_year ?? null,
                'is_default' => true,
            ],
        ], 201);
    }

    public function destroy(string $id): JsonResponse
    {
        $user = auth()->user();

        if (! $user->hasStripeId()) {
            return response()->json(['message' => 'Moyen de paiement introuvable.'], 404);
        }

        $method = collect($user->paymentMethods())->firstWhere('id', $id);

        if (! $method) {
            return response()->json(['message' => 'Moyen de paiement introuvable.'], 404);
        }

        $method->delete();

        return response()->json(['message' => 'Moyen de paiement supprimé.']);
    }

    public function setDefault(string $id): JsonResponse
    {
        $user = auth()->user();

        if (! $user->hasStripeId()) {
            return response()->json(['message' => 'Moyen de paiement introuvable.'], 404);
        }

        $method = collect($user->paymentMethods())->firstWhere('id', $id);

        if (! $method) {
            return response()->json(['message' => 'Moyen de paiement introuvable.'], 404);
        }

        $user->updateDefaultPaymentMethod($id);

        return response()->json(['message' => 'Carte par défaut mise à jour.']);
    }
}
