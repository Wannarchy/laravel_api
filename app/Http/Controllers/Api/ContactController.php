<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreContactRequest;
use App\Http\Resources\ContactMessageResource;
use App\Models\ContactMessage;
use Illuminate\Http\JsonResponse;

class ContactController extends Controller
{
    public function store(StoreContactRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $message = ContactMessage::create([
            'user_id' => auth()->id(),
            'email' => $validated['email'],
            'sujet' => $validated['sujet'],
            'message' => $validated['message'],
            'status' => ContactMessage::STATUS_PENDING,
            'created_at' => now(),
        ]);

        return response()->json([
            'data' => new ContactMessageResource($message),
        ], 201);
    }
}
