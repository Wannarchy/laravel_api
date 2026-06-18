<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Product::with('category')
            ->where('is_available', true);

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->integer('category_id'));
        }

        if ($request->filled('is_featured')) {
            $query->where('is_featured', filter_var($request->is_featured, FILTER_VALIDATE_BOOLEAN));
        }

        $query->orderBy('featured_order')->orderBy('name');

        if ($request->filled('page') || $request->filled('per_page')) {
            $perPage = min(48, max(1, $request->integer('per_page', 12)));

            return ProductResource::collection(
                $query->paginate($perPage)->appends($request->query())
            )->response();
        }

        $products = $query->get();

        return response()->json([
            'data' => ProductResource::collection($products),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $product = Product::with(['category'])
            ->where('is_available', true)
            ->find($id);

        if (! $product) {
            return response()->json(['message' => 'Produit introuvable.'], 404);
        }

        return response()->json([
            'data' => new ProductResource($product),
        ]);
    }
}
