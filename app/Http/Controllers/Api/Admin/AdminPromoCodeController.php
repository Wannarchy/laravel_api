<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\PromoCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminPromoCodeController extends Controller
{
    public function index(): JsonResponse
    {
        $promoCodes = PromoCode::orderByDesc('created_at')->get();

        return response()->json(['data' => $promoCodes]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:promo_codes,code'],
            'type' => ['required', Rule::in(['percent', 'fixed'])],
            'value' => ['required', 'numeric', 'min:0'],
            'min_amount' => ['nullable', 'numeric', 'min:0'],
            'max_uses' => ['nullable', 'integer', 'min:1'],
            'expires_at' => ['nullable', 'date'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));

        $promo = PromoCode::create($validated);

        return response()->json(['data' => $promo], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $promo = PromoCode::find($id);

        if (! $promo) {
            return response()->json(['message' => 'Code promo introuvable.'], 404);
        }

        $validated = $request->validate([
            'code' => ['sometimes', 'string', 'max:50', Rule::unique('promo_codes', 'code')->ignore($id)],
            'type' => ['sometimes', Rule::in(['percent', 'fixed'])],
            'value' => ['sometimes', 'numeric', 'min:0'],
            'min_amount' => ['nullable', 'numeric', 'min:0'],
            'max_uses' => ['nullable', 'integer', 'min:1'],
            'expires_at' => ['nullable', 'date'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $promo->update($validated);

        return response()->json(['data' => $promo->fresh()]);
    }

    public function destroy(int $id): JsonResponse
    {
        $promo = PromoCode::find($id);

        if (! $promo) {
            return response()->json(['message' => 'Code promo introuvable.'], 404);
        }

        $promo->delete();

        return response()->json(['message' => 'Code promo supprimé.']);
    }
}
