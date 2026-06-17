<?php

namespace App\Services;

use App\Models\Product;
use Laravel\Cashier\Cashier;
use RuntimeException;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;
use Throwable;

class StripeProductSyncService
{
    public function isConfigured(): bool
    {
        return (bool) config('cashier.secret');
    }

    /**
     * Crée ou met à jour le produit Stripe et ses prix récurrents mensuel / annuel.
     */
    public function sync(Product $product, bool $forcePrices = false): Product
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('STRIPE_SECRET n\'est pas configuré.');
        }

        $stripe = Cashier::stripe();
        $currency = strtolower((string) config('cashier.currency', 'eur'));

        $stripeProductId = $this->ensureStripeProduct($product, $stripe);

        $monthlyId = $this->ensureRecurringPrice(
            $stripe,
            $product,
            $stripeProductId,
            'month',
            (float) $product->price_monthly,
            $product->stripe_price_id_monthly,
            $forcePrices,
            $currency
        );

        $yearlyId = $this->ensureRecurringPrice(
            $stripe,
            $product,
            $stripeProductId,
            'year',
            (float) $product->price_yearly,
            $product->stripe_price_id_yearly,
            $forcePrices,
            $currency
        );

        $product->update([
            'stripe_product_id' => $stripeProductId,
            'stripe_price_id_monthly' => $monthlyId,
            'stripe_price_id_yearly' => $yearlyId,
        ]);

        return $product->fresh();
    }

    private function ensureStripeProduct(Product $product, StripeClient $stripe): string
    {
        if ($product->stripe_product_id) {
            try {
                $stripe->products->update($product->stripe_product_id, [
                    'name' => $product->name,
                    'metadata' => ['app_product_id' => (string) $product->id],
                ]);

                return $product->stripe_product_id;
            } catch (ApiErrorException) {
                // Produit Stripe supprimé ou invalide : on en recrée un.
            }
        }

        $stripeProduct = $stripe->products->create([
            'name' => $product->name,
            'metadata' => ['app_product_id' => (string) $product->id],
        ]);

        return $stripeProduct->id;
    }

    private function ensureRecurringPrice(
        StripeClient $stripe,
        Product $product,
        string $stripeProductId,
        string $interval,
        float $amount,
        ?string $existingPriceId,
        bool $forcePrices,
        string $currency
    ): string {
        $unitAmount = (int) round($amount * 100);

        if (! $forcePrices && $existingPriceId) {
            try {
                $existing = $stripe->prices->retrieve($existingPriceId);

                if (
                    $existing->active
                    && (int) $existing->unit_amount === $unitAmount
                    && ($existing->recurring->interval ?? null) === $interval
                    && ($existing->currency ?? '') === $currency
                ) {
                    return $existingPriceId;
                }

                $stripe->prices->update($existingPriceId, ['active' => false]);
            } catch (Throwable) {
                // Prix Stripe introuvable : on en crée un nouveau.
            }
        } elseif ($existingPriceId && $forcePrices) {
            try {
                $stripe->prices->update($existingPriceId, ['active' => false]);
            } catch (Throwable) {
                // Ignorer si déjà archivé ou supprimé.
            }
        }

        $price = $stripe->prices->create([
            'product' => $stripeProductId,
            'currency' => $currency,
            'unit_amount' => $unitAmount,
            'recurring' => ['interval' => $interval],
            'metadata' => [
                'app_product_id' => (string) $product->id,
                'billing_cycle' => $interval === 'year' ? 'yearly' : 'monthly',
            ],
        ]);

        return $price->id;
    }
}
