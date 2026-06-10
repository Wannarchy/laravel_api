<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SubscriptionResource;
use App\Models\Subscription;
use Illuminate\Http\JsonResponse;

class SubscriptionController extends Controller
{
    public function index(): JsonResponse
    {
        $subscriptions = Subscription::with('product')
            ->where('user_id', auth()->id())
            ->orderByDesc('start_date')
            ->get();

        return response()->json([
            'data' => SubscriptionResource::collection($subscriptions),
        ]);
    }

    public function cancel(int $id): JsonResponse
    {
        $subscription = Subscription::where('user_id', auth()->id())
            ->where('status', 'active')
            ->find($id);

        if (! $subscription) {
            return response()->json(['message' => 'Abonnement introuvable ou déjà annulé.'], 404);
        }

        $subscription->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        return response()->json([
            'data' => new SubscriptionResource($subscription->load('product')),
            'message' => 'Abonnement annulé. Il restera actif jusqu\'à la fin de la période en cours.',
        ]);
    }
}
