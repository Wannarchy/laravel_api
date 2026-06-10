<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ChatController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_message' => ['required', 'string', 'max:2000'],
            'session_id' => ['nullable', 'string', 'max:100'],
        ]);

        $sessionId = $validated['session_id'] ?? Str::random(32);
        $botResponse = $this->generateBotResponse($validated['user_message']);

        $log = ChatLog::create([
            'user_id' => auth()->id(),
            'session_id' => $sessionId,
            'user_message' => $validated['user_message'],
            'bot_response' => $botResponse,
            'created_at' => now(),
        ]);

        return response()->json([
            'data' => [
                'session_id' => $sessionId,
                'user_message' => $log->user_message,
                'bot_response' => $log->bot_response,
                'created_at' => $log->created_at,
            ],
        ], 201);
    }

    public function history(Request $request): JsonResponse
    {
        $request->validate([
            'session_id' => ['required', 'string', 'max:100'],
        ]);

        $logs = ChatLog::where('user_id', auth()->id())
            ->where('session_id', $request->session_id)
            ->orderBy('created_at')
            ->get(['id', 'user_message', 'bot_response', 'created_at']);

        return response()->json(['data' => $logs]);
    }

    private function generateBotResponse(string $message): string
    {
        $lower = mb_strtolower($message);

        if (str_contains($lower, 'abonnement') || str_contains($lower, 'résilier') || str_contains($lower, 'resilier')) {
            return 'Vous pouvez gérer vos abonnements depuis votre espace compte → "Mes abonnements". La résiliation prend effet à la fin de la période en cours, sans frais supplémentaires.';
        }

        if (str_contains($lower, 'essai') || str_contains($lower, 'essaie')) {
            return 'Certains de nos services proposent une période d\'essai gratuite. Consultez les pages produits de notre catalogue pour voir les offres d\'essai disponibles.';
        }

        if (str_contains($lower, 'prix') || str_contains($lower, 'tarif')) {
            return 'Nos tarifs varient selon les produits et cycles de facturation (mensuel ou annuel). Consultez notre catalogue pour les détails.';
        }

        return 'Je ne suis pas sûr de comprendre votre demande. Pour une assistance personnalisée, n\'hésitez pas à utiliser le formulaire de contact ou à nous écrire à contact@cyna-it.fr.';
    }
}
