<?php

namespace App\Rules;

use App\Models\PromoCode;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidPromoCode implements ValidationRule
{
    public function __construct(protected float $amount) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $promo = PromoCode::where('code', $value)->first();

        if (! $promo) {
            $fail('Code promo invalide.');

            return;
        }

        if ((int) $promo->is_active !== 1) {
            $fail('Ce code promo n\'est plus actif.');

            return;
        }

        if ($promo->expires_at && $promo->expires_at->lt(today())) {
            $fail('Ce code promo a expiré.');

            return;
        }

        if ($promo->max_uses !== null && $promo->uses_count >= $promo->max_uses) {
            $fail('Ce code promo a atteint sa limite d\'utilisation.');

            return;
        }

        if ($this->amount < (float) $promo->min_amount) {
            $fail('Montant minimum non atteint pour ce code promo.');
        }
    }
}
