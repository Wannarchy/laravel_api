<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\StripeProductSyncService;
use Illuminate\Http\JsonResponse;
use Throwable;

class AdminProductController extends Controller
{
    public function __construct(
        private readonly StripeProductSyncService $stripeSync
    ) {}

    public function index(): JsonResponse
    {
        $products = Product::with(['category'])
            ->orderBy('featured_order')
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => ProductResource::collection($products),
        ]);
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $data = $this->sanitizeProductData($request->validated());

        $product = Product::create($data);
        [$product, $stripeWarning] = $this->syncWithStripe($product);

        return response()->json([
            'data' => new ProductResource($product->load(['category'])),
            'message' => $this->buildMessage('Produit créé', $stripeWarning),
        ], 201);
    }

    public function update(StoreProductRequest $request, int $id): JsonResponse
    {
        $product = Product::find($id);

        if (! $product) {
            return response()->json(['message' => 'Produit introuvable.'], 404);
        }

        $data = $this->sanitizeProductData($request->validated());
        $pricesChanged = $this->pricesChanged($product, $data);

        $product->update($data);
        [$product, $stripeWarning] = $this->syncWithStripe($product->fresh(), $pricesChanged);

        return response()->json([
            'data' => new ProductResource($product->load(['category'])),
            'message' => $this->buildMessage('Produit mis à jour', $stripeWarning),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $product = Product::find($id);

        if (! $product) {
            return response()->json(['message' => 'Produit introuvable.'], 404);
        }

        $product->delete();

        return response()->json(['message' => 'Produit supprimé.']);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function sanitizeProductData(array $data): array
    {
        if (array_key_exists('image_path', $data) && trim((string) $data['image_path']) === '') {
            unset($data['image_path']);
        }

        unset($data['stripe_product_id'], $data['stripe_price_id_monthly'], $data['stripe_price_id_yearly']);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function pricesChanged(Product $product, array $data): bool
    {
        if (array_key_exists('price_monthly', $data)
            && (float) $data['price_monthly'] !== (float) $product->price_monthly) {
            return true;
        }

        if (array_key_exists('price_yearly', $data)
            && (float) $data['price_yearly'] !== (float) $product->price_yearly) {
            return true;
        }

        return array_key_exists('name', $data) && trim((string) $data['name']) !== $product->name;
    }

    /**
     * @return array{0: Product, 1: string|null}
     */
    private function syncWithStripe(Product $product, bool $forcePrices = false): array
    {
        if (! $this->stripeSync->isConfigured()) {
            return [$product, 'Stripe non configuré (STRIPE_SECRET manquant).'];
        }

        try {
            return [$this->stripeSync->sync($product, $forcePrices), null];
        } catch (Throwable $e) {
            report($e);

            return [$product, $e->getMessage()];
        }
    }

    private function buildMessage(string $base, ?string $stripeWarning): string
    {
        if ($stripeWarning === null) {
            return $base.' et synchronisé avec Stripe.';
        }

        return $base.'. Synchronisation Stripe échouée : '.$stripeWarning;
    }
}
