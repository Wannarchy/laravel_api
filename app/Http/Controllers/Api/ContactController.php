<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreContactRequest;
use App\Models\ContactMessage;
use Illuminate\Http\JsonResponse;

class ContactController extends Controller
{
    public function store(StoreContactRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $message = ContactMessage::create([
            'user_id' => null,
            'email' => $validated['email'],
            'sujet' => $validated['sujet'],
            'message' => $validated['message'],
            'created_at' => now(),
        ]);

        return response()->json([
            'data' => [
                'id' => $message->id,
                'email' => $message->email,
                'sujet' => $message->sujet,
                'message' => $message->message,
                'created_at' => $message->created_at,
            ],
        ], 201);
    }
}
