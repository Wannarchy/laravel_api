<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Laravel\Cashier\Cashier;
use Throwable;

class SyncStripeProducts extends Command
{
    protected $signature = 'stripe:sync-products
                            {--force : Recrée les produits et prix Stripe même s\'ils existent déjà}';

    protected $description = 'Crée les produits et prix récurrents Stripe pour chaque produit et enregistre les IDs en base.';

    public function handle(): int
    {
        if (! config('cashier.secret')) {
            $this->error('STRIPE_SECRET n\'est pas configuré. Renseignez la clé Stripe dans le .env.');

            return self::FAILURE;
        }

        $stripe = Cashier::stripe();
        $currency = strtolower((string) config('cashier.currency', 'eur'));
        $force = (bool) $this->option('force');

        $products = Product::orderBy('id')->get();

        if ($products->isEmpty()) {
            $this->warn('Aucun produit en base.');

            return self::SUCCESS;
        }

        $created = 0;
        $skipped = 0;

        foreach ($products as $product) {
            $hasAll = $product->stripe_product_id
                && $product->stripe_price_id_monthly
                && $product->stripe_price_id_yearly;

            if ($hasAll && ! $force) {
                $this->line("• {$product->name} : déjà configuré, ignoré.");
                $skipped++;

                continue;
            }

            try {
                $stripeProduct = $stripe->products->create([
                    'name' => $product->name,
                    'metadata' => ['app_product_id' => (string) $product->id],
                ]);

                $monthly = $stripe->prices->create([
                    'product' => $stripeProduct->id,
                    'currency' => $currency,
                    'unit_amount' => (int) round(((float) $product->price_monthly) * 100),
                    'recurring' => ['interval' => 'month'],
                ]);

                $yearly = $stripe->prices->create([
                    'product' => $stripeProduct->id,
                    'currency' => $currency,
                    'unit_amount' => (int) round(((float) $product->price_yearly) * 100),
                    'recurring' => ['interval' => 'year'],
                ]);

                $product->update([
                    'stripe_product_id' => $stripeProduct->id,
                    'stripe_price_id_monthly' => $monthly->id,
                    'stripe_price_id_yearly' => $yearly->id,
                ]);

                $this->info("✔ {$product->name} : produit + prix Stripe créés.");
                $created++;
            } catch (Throwable $e) {
                $this->error("✘ {$product->name} : {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info("Terminé. {$created} produit(s) configuré(s), {$skipped} ignoré(s).");

        return self::SUCCESS;
    }
}
