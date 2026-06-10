<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;

class AdminCategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = Category::orderBy('sort_order')->get();

        return response()->json([
            'data' => CategoryResource::collection($categories),
        ]);
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = Category::create($request->validated());

        return response()->json([
            'data' => new CategoryResource($category),
        ], 201);
    }

    public function update(StoreCategoryRequest $request, int $id): JsonResponse
    {
        $category = Category::find($id);

        if (! $category) {
            return response()->json(['message' => 'Catégorie introuvable.'], 404);
        }

        $category->update($request->validated());

        return response()->json([
            'data' => new CategoryResource($category->fresh()),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $category = Category::find($id);

        if (! $category) {
            return response()->json(['message' => 'Catégorie introuvable.'], 404);
        }

        $category->delete();

        return response()->json(['message' => 'Catégorie supprimée.']);
    }
}
