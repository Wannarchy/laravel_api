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
        $code = strtoupper(trim((string) $value));
        $promo = PromoCode::where('code', $code)->first();

        if (! $promo) {
            $fail('Code promo invalide.');

            return;
        }

        if (! $promo->is_active) {
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
