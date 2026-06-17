<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\StripeProductSyncService;
use Illuminate\Console\Command;
use Throwable;

class SyncStripeProducts extends Command
{
    protected $signature = 'stripe:sync-products
                            {--force : Recrée les prix Stripe même si déjà configurés}';

    protected $description = 'Crée les produits et prix récurrents Stripe pour chaque produit et enregistre les IDs en base.';

    public function handle(StripeProductSyncService $stripeSync): int
    {
        if (! $stripeSync->isConfigured()) {
            $this->error('STRIPE_SECRET n\'est pas configuré. Renseignez la clé Stripe dans le .env.');

            return self::FAILURE;
        }

        $force = (bool) $this->option('force');
        $products = Product::orderBy('id')->get();

        if ($products->isEmpty()) {
            $this->warn('Aucun produit en base.');

            return self::SUCCESS;
        }

        $synced = 0;
        $failed = 0;

        foreach ($products as $product) {
            try {
                $stripeSync->sync($product, $force);
                $this->info("✔ {$product->name} : synchronisé avec Stripe.");
                $synced++;
            } catch (Throwable $e) {
                $this->error("✘ {$product->name} : {$e->getMessage()}");
                $failed++;
            }
        }

        $this->newLine();
        $this->info("Terminé. {$synced} produit(s) synchronisé(s), {$failed} échec(s).");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
