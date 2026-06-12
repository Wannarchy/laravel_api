<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\HomepageContent;
use App\Models\HomepageSlide;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminHomepageController extends Controller
{
    public function updateSlides(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'slides' => ['required', 'array', 'min:1'],
            'slides.*.id' => ['nullable', 'integer', 'exists:homepage_slides,id'],
            'slides.*.title' => ['required', 'string', 'max:200'],
            'slides.*.subtitle' => ['nullable', 'string', 'max:500'],
            'slides.*.image_path' => ['nullable', 'string', 'max:255'],
            'slides.*.link_url' => ['nullable', 'string', 'max:255'],
            'slides.*.sort_order' => ['nullable', 'integer'],
            'slides.*.is_active' => ['nullable', 'boolean'],
        ]);

        $slides = [];

        foreach ($validated['slides'] as $slideData) {
            if (! empty($slideData['id'])) {
                $slide = HomepageSlide::find($slideData['id']);
                $slide->update($slideData);
            } else {
                $slide = HomepageSlide::create($slideData);
            }
            $slides[] = $slide->fresh();
        }

        return response()->json(['data' => $slides]);
    }

    public function updateContent(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'content_text' => ['required', 'string'],
        ]);

        $content = HomepageContent::first();

        if ($content) {
            $content->update($validated);
        } else {
            $content = HomepageContent::create($validated);
        }

        return response()->json(['data' => $content]);
    }

    public function destroySlide(int $id): JsonResponse
    {
        $slide = HomepageSlide::find($id);

        if (! $slide) {
            return response()->json(['message' => 'Slide introuvable.'], 404);
        }

        $slide->delete();

        return response()->json(['message' => 'Slide supprimée.']);
    }
}
