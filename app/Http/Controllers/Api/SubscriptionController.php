<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SubscriptionResource;
use App\Models\ProductSubscription;
use Illuminate\Http\JsonResponse;
use Laravel\Cashier\Subscription as CashierSubscription;

class SubscriptionController extends Controller
{
    public function index(): JsonResponse
    {
        $subscriptions = ProductSubscription::with('product')
            ->where('user_id', auth()->id())
            ->orderByDesc('start_date')
            ->get();

        return response()->json([
            'data' => SubscriptionResource::collection($subscriptions),
        ]);
    }

    public function cancel(int $id): JsonResponse
    {
        $subscription = ProductSubscription::where('user_id', auth()->id())
            ->where('status', 'active')
            ->find($id);

        if (! $subscription) {
            return response()->json(['message' => 'Abonnement introuvable ou déjà annulé.'], 404);
        }

        if ($subscription->stripe_subscription_id) {
            $cashierSubscription = CashierSubscription::query()
                ->where('stripe_id', $subscription->stripe_subscription_id)
                ->first();

            $cashierSubscription?->cancel();
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
