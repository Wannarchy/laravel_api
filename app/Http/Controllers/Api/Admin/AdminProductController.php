<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;

class AdminProductController extends Controller
{
    public function index(): JsonResponse
    {
        $products = Product::with('category')
            ->orderBy('featured_order')
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => ProductResource::collection($products),
        ]);
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = Product::create($request->validated());

        return response()->json([
            'data' => new ProductResource($product->load('category')),
        ], 201);
    }

    public function update(StoreProductRequest $request, int $id): JsonResponse
    {
        $product = Product::find($id);

        if (! $product) {
            return response()->json(['message' => 'Produit introuvable.'], 404);
        }

        $product->update($request->validated());

        return response()->json([
            'data' => new ProductResource($product->fresh()->load('category')),
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
}
