<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UploadImageRequest;
use App\Http\Resources\ProductImageResource;
use App\Models\Product;
use App\Models\ProductImage;
use App\Services\CloudinaryUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminProductImageController extends Controller
{
    public function store(UploadImageRequest $request, int $productId, CloudinaryUploadService $cloudinary): JsonResponse
    {
        $product = Product::find($productId);

        if (! $product) {
            return response()->json(['message' => 'Produit introuvable.'], 404);
        }

        $upload = $cloudinary->upload($request->file('image'), 'products/gallery');
        $sortOrder = ((int) $product->images()->max('sort_order')) + 1;

        $image = ProductImage::create([
            'product_id' => $product->id,
            'image_path' => $upload['url'],
            'caption' => $request->string('caption')->trim()->toString() ?: null,
            'sort_order' => $sortOrder,
        ]);

        return response()->json([
            'data' => new ProductImageResource($image),
            'message' => 'Image ajoutée au carrousel.',
        ], 201);
    }

    public function destroy(int $productId, int $imageId): JsonResponse
    {
        $image = ProductImage::query()
            ->where('product_id', $productId)
            ->where('id', $imageId)
            ->first();

        if (! $image) {
            return response()->json(['message' => 'Image introuvable.'], 404);
        }

        $image->delete();

        return response()->json(['message' => 'Image supprimée du carrousel.']);
    }

    public function updateSort(Request $request, int $productId): JsonResponse
    {
        $product = Product::find($productId);

        if (! $product) {
            return response()->json(['message' => 'Produit introuvable.'], 404);
        }

        $data = $request->validate([
            'orders' => ['required', 'array'],
            'orders.*.id' => ['required', 'integer'],
            'orders.*.sort_order' => ['required', 'integer', 'min:1'],
        ]);

        foreach ($data['orders'] as $row) {
            ProductImage::query()
                ->where('product_id', $product->id)
                ->where('id', $row['id'])
                ->update(['sort_order' => $row['sort_order']]);
        }

        $images = $product->images()->get();

        return response()->json([
            'data' => ProductImageResource::collection($images),
            'message' => 'Ordre du carrousel mis à jour.',
        ]);
    }
}
