<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ValidatePromoRequest;
use App\Models\PromoCode;
use Illuminate\Http\JsonResponse;

class PromoCodeController extends Controller
{
    public function validate(ValidatePromoRequest $request): JsonResponse
    {
        $promo = PromoCode::where('code', $request->code)->firstOrFail();

        $discount = $promo->type === 'fixed'
            ? (float) $promo->value
            : (float) $request->amount * ((float) $promo->value / 100);

        $finalAmount = max(0, (float) $request->amount - $discount);

        return response()->json([
            'data' => [
                'code' => $promo->code,
                'type' => $promo->type,
                'value' => $promo->value,
                'discount' => round($discount, 2),
                'final_amount' => round($finalAmount, 2),
            ],
        ]);
    }
}
