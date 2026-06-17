<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UploadImageRequest;
use App\Services\CloudinaryUploadService;
use Illuminate\Http\JsonResponse;

class AdminUploadController extends Controller
{
    public function store(UploadImageRequest $request, CloudinaryUploadService $cloudinary): JsonResponse
    {
        $folder = $request->string('folder', 'products')->toString();
        $upload = $cloudinary->upload($request->file('image'), $folder);

        return response()->json([
            'data' => $upload,
            'message' => 'Image uploadée sur Cloudinary.',
        ], 201);
    }
}
