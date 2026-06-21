<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductSubscription;
use App\Models\PromoCode;
use App\Models\User;
use App\Notifications\OrderConfirmationNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class OrderFulfillmentService
{
    public function calculateLineItems(array $items): Collection
    {
        return collect($items)->map(function (array $item) {
            $product = Product::findOrFail($item['product_id']);

            if (! $product->isPurchasable()) {
                throw new InvalidArgumentException(
                    "Le produit « {$product->name} » est indisponible (rupture de stock)."
                );
            }

            $cycle = $item['cycle'];
            $price = $cycle === 'yearly'
                ? (float) $product->price_yearly
                : (float) $product->price_monthly;

            return [
                'product' => $product,
                'cycle' => $cycle,
                'price' => $price,
            ];
        });
    }

    public function calculateTotal(Collection $lineItems, ?string $promoCode = null): float
    {
        $subtotal = $lineItems->sum('price');
        $total = $subtotal;

        if ($promoCode) {
            $promo = PromoCode::where('code', strtoupper(trim($promoCode)))->first();

            if ($promo && $this->isPromoValid($promo, $subtotal)) {
                $total = $this->applyDiscount($promo, $subtotal);
            }
        }

        return round($total, 2);
    }

    public function stripePriceId(Product $product, string $cycle): string
    {
        $priceId = $cycle === 'yearly'
            ? $product->stripe_price_id_yearly
            : $product->stripe_price_id_monthly;

        if (! $priceId) {
            throw new InvalidArgumentException(
                "Le produit « {$product->name} » n'a pas de prix Stripe configuré pour le cycle {$cycle}."
            );
        }

        return $priceId;
    }

    public function fulfill(
        User $user,
        array $items,
        string $billingName,
        string $billingAddress,
        ?string $promoCode = null,
        ?string $stripePaymentIntent = null,
        ?string $stripeCheckoutSessionId = null,
        ?string $cardLast4 = null,
        array $stripeSubscriptionIds = [],
        ?string $shippingName = null,
        ?string $shippingAddress = null,
    ): Order {
        $order = DB::transaction(function () use (
            $user,
            $items,
            $billingName,
            $billingAddress,
            $promoCode,
            $stripePaymentIntent,
            $stripeCheckoutSessionId,
            $cardLast4,
            $stripeSubscriptionIds,
            $shippingName,
            $shippingAddress,
        ) {
            $lineItems = $this->calculateLineItems($items);
            $subtotal = round((float) $lineItems->sum('price'), 2);
            $total = $this->calculateTotal($lineItems, $promoCode);
            $promoDiscount = round(max(0, $subtotal - $total), 2);
            $taxAmount = round($total - ($total / 1.2), 2);
            $appliedPromoCode = null;

            if ($promoCode) {
                $promo = PromoCode::where('code', strtoupper(trim($promoCode)))->first();

                if ($promo && $this->isPromoValid($promo, $subtotal)) {
                    $promo->increment('uses_count');
                    $appliedPromoCode = strtoupper(trim($promoCode));
                }
            }

            $isPaid = $stripePaymentIntent || $stripeCheckoutSessionId || count($stripeSubscriptionIds) > 0;

            $order = Order::create([
                'user_id' => $user->id,
                'total' => $total,
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'promo_discount' => $promoDiscount,
                'promo_code' => $appliedPromoCode,
                'billing_name' => $billingName,
                'billing_address' => $billingAddress,
                'shipping_name' => $shippingName,
                'shipping_address' => $shippingAddress,
                'stripe_payment_intent' => $stripePaymentIntent,
                'stripe_checkout_session_id' => $stripeCheckoutSessionId,
                'card_last4' => $cardLast4 ?? $user->pm_last_four,
                'payment_brand' => $user->pm_type,
                'status' => $isPaid ? 'paid' : 'pending',
            ]);

            foreach ($lineItems as $index => $line) {
                $order->items()->create([
                    'product_id' => $line['product']->id,
                    'cycle' => $line['cycle'],
                    'price' => $line['price'],
                ]);

                if ($order->status === 'paid') {
                    Product::where('id', $line['product']->id)
                        ->where('stock', '>', 0)
                        ->decrement('stock');

                    $nextBilling = $line['cycle'] === 'yearly'
                        ? now()->addYear()
                        : now()->addMonth();

                    ProductSubscription::create([
                        'user_id' => $user->id,
                        'order_id' => $order->id,
                        'product_id' => $line['product']->id,
                        'cycle' => $line['cycle'],
                        'price' => $line['price'],
                        'status' => 'active',
                        'stripe_subscription_id' => $stripeSubscriptionIds[$index] ?? null,
                        'start_date' => now()->toDateString(),
                        'next_billing' => $nextBilling->toDateString(),
                    ]);
                }
            }

            return $order->load(['items.product']);
        });

        if ($order->status === 'paid') {
            $user->notify(new OrderConfirmationNotification($order));
        }

        return $order;
    }

    private function isPromoValid(PromoCode $promo, float $amount): bool
    {
        if (! $promo->is_active) {
            return false;
        }

        if ($promo->expires_at && $promo->expires_at->isPast()) {
            return false;
        }

        if ($promo->max_uses !== null && $promo->uses_count >= $promo->max_uses) {
            return false;
        }

        if ($amount < (float) $promo->min_amount) {
            return false;
        }

        return true;
    }

    private function applyDiscount(PromoCode $promo, float $amount): float
    {
        if ($promo->type === 'fixed') {
            return max(0, $amount - (float) $promo->value);
        }

        return max(0, $amount - ($amount * ((float) $promo->value / 100)));
    }

    public function fulfillCheckoutSession(object $session): ?Order
    {
        $metadata = $session->metadata ?? null;

        if (! $metadata || empty($metadata->user_id)) {
            return null;
        }

        $existing = Order::where('stripe_checkout_session_id', $session->id)->first();

        if ($existing) {
            return $existing->load(['items.product']);
        }

        $user = User::find((int) $metadata->user_id);

        if (! $user) {
            return null;
        }

        $items = json_decode($metadata->items ?? '[]', true) ?: [];
        $stripeSubscriptionId = is_string($session->subscription)
            ? $session->subscription
            : ($session->subscription->id ?? null);

        $stripeSubscriptionIds = array_fill(0, count($items), $stripeSubscriptionId);

        return $this->fulfill(
            user: $user,
            items: $items,
            billingName: $metadata->billing_name ?? $user->prenom.' '.$user->nom,
            billingAddress: $metadata->billing_address ?? '',
            promoCode: ! empty($metadata->promo_code) ? $metadata->promo_code : null,
            stripeCheckoutSessionId: $session->id,
            stripeSubscriptionIds: $stripeSubscriptionIds,
        );
    }
}
