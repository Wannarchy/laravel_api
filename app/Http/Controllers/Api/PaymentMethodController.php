<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserPaymentMethod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentMethodController extends Controller
{
    public function index(): JsonResponse
    {
        $methods = UserPaymentMethod::where('user_id', auth()->id())
            ->orderByDesc('is_default')
            ->get();

        return response()->json(['data' => $methods]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'card_holder' => ['required', 'string', 'max:120'],
            'card_last4' => ['required', 'string', 'size:4'],
            'card_brand' => ['nullable', 'string', 'max:20'],
            'exp_month' => ['required', 'integer', 'min:1', 'max:12'],
            'exp_year' => ['required', 'integer', 'min:'.date('Y')],
            'is_default' => ['nullable', 'boolean'],
        ]);

        if ($request->boolean('is_default')) {
            UserPaymentMethod::where('user_id', auth()->id())->update(['is_default' => false]);
        }

        $method = UserPaymentMethod::create([
            ...$validated,
            'user_id' => auth()->id(),
            'card_brand' => $validated['card_brand'] ?? 'Visa',
        ]);

        return response()->json(['data' => $method], 201);
    }

    public function destroy(int $id): JsonResponse
    {
        $method = UserPaymentMethod::where('user_id', auth()->id())->find($id);

        if (! $method) {
            return response()->json(['message' => 'Moyen de paiement introuvable.'], 404);
        }

        $method->delete();

        return response()->json(['message' => 'Moyen de paiement supprimé.']);
    }
}
