<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HomepageContent;
use App\Models\HomepageSlide;
use Illuminate\Http\JsonResponse;

class HomepageController extends Controller
{
    public function index(): JsonResponse
    {
        $slides = HomepageSlide::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $content = HomepageContent::first();

        return response()->json([
            'data' => [
                'slides' => $slides,
                'content' => $content,
            ],
        ]);
    }
}
