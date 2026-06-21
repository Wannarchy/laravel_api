<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SubscriptionResource;
use App\Models\ProductSubscription;
use App\Services\OrderFulfillmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Laravel\Cashier\Subscription as CashierSubscription;

class SubscriptionController extends Controller
{
    public function __construct(
        private OrderFulfillmentService $orderFulfillment,
    ) {}

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
            ->whereNull('cancelled_at')
            ->find($id);

        if (! $subscription) {
            return response()->json([
                'message' => 'Abonnement introuvable, déjà résilié ou résiliation déjà programmée.',
            ], 404);
        }

        if ($subscription->stripe_subscription_id) {
            $cashierSubscription = CashierSubscription::query()
                ->where('stripe_id', $subscription->stripe_subscription_id)
                ->first();

            $cashierSubscription?->cancel();
        }

        $subscription->update([
            'cancelled_at' => now(),
        ]);

        return response()->json([
            'data' => new SubscriptionResource($subscription->load('product')),
            'message' => 'Abonnement résilié. Il restera actif jusqu\'à la fin de la période en cours.',
        ]);
    }

    public function changeCycle(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'cycle' => ['required', 'in:monthly,yearly'],
        ]);

        $subscription = ProductSubscription::with('product')
            ->where('user_id', auth()->id())
            ->where('status', 'active')
            ->whereNull('cancelled_at')
            ->find($id);

        if (! $subscription) {
            return response()->json(['message' => 'Abonnement introuvable ou non modifiable.'], 404);
        }

        $newCycle = $validated['cycle'];

        if ($subscription->cycle === $newCycle) {
            return response()->json(['message' => 'Ce cycle de facturation est déjà actif.'], 422);
        }

        $product = $subscription->product;

        if (! $product) {
            return response()->json(['message' => 'Produit associé introuvable.'], 422);
        }

        try {
            $priceId = $this->orderFulfillment->stripePriceId($product, $newCycle);
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        if ($subscription->stripe_subscription_id) {
            $cashierSubscription = CashierSubscription::query()
                ->where('stripe_id', $subscription->stripe_subscription_id)
                ->first();

            if (! $cashierSubscription) {
                return response()->json(['message' => 'Abonnement Stripe introuvable.'], 422);
            }

            try {
                $cashierSubscription->swap($priceId);
            } catch (\Exception $exception) {
                return response()->json([
                    'message' => 'Impossible de modifier le cycle : '.$exception->getMessage(),
                ], 422);
            }
        }

        $newPrice = $newCycle === 'yearly'
            ? (float) $product->price_yearly
            : (float) $product->price_monthly;

        $subscription->update([
            'cycle' => $newCycle,
            'price' => $newPrice,
        ]);

        return response()->json([
            'data' => new SubscriptionResource($subscription->fresh()->load('product')),
            'message' => 'Cycle de facturation mis à jour.',
        ]);
    }
}
