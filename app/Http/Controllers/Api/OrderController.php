<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderFulfillmentService;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;

class OrderController extends Controller
{
    public function __construct(
        private OrderFulfillmentService $orderFulfillment,
    ) {}

    public function index(): JsonResponse
    {
        $orders = Order::with(['items.product'])
            ->where('user_id', auth()->id())
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'data' => OrderResource::collection($orders),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $order = Order::with(['items.product'])
            ->where('user_id', auth()->id())
            ->find($id);

        if (! $order) {
            return response()->json(['message' => 'Commande introuvable.'], 404);
        }

        return response()->json([
            'data' => new OrderResource($order),
        ]);
    }

    public function store(StoreOrderRequest $request): JsonResponse
    {
        $user = auth()->user();

        try {
            $lineItems = $this->orderFulfillment->calculateLineItems($request->items);
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        if (! $request->filled('payment_method')) {
            return response()->json([
                'message' => 'Un moyen de paiement Stripe est requis. Utilisez payment_method ou POST /api/billing/checkout.',
            ], 422);
        }

        $user->createOrGetStripeCustomer([
            'name' => $request->billing_name,
            'email' => $user->email,
        ]);

        $user->updateDefaultPaymentMethod($request->payment_method);

        $stripeSubscriptionIds = [];

        try {
            foreach ($lineItems as $line) {
                $priceId = $this->orderFulfillment->stripePriceId($line['product'], $line['cycle']);
                $subscription = $user->newSubscription(
                    'product-'.$line['product']->id,
                    $priceId
                )->create($request->payment_method);

                $stripeSubscriptionIds[] = $subscription->stripe_id;
            }
        } catch (\Exception $exception) {
            return response()->json([
                'message' => 'Paiement refusé : '.$exception->getMessage(),
            ], 422);
        }

        $order = $this->orderFulfillment->fulfill(
            user: $user,
            items: $request->items,
            billingName: $request->billing_name,
            billingAddress: $request->billing_address,
            promoCode: $request->promo_code,
            cardLast4: $user->fresh()->pm_last_four,
            stripeSubscriptionIds: $stripeSubscriptionIds,
            shippingName: $request->shipping_name,
            shippingAddress: $request->shipping_address,
        );

        if ($user->pm_type && ! $order->payment_brand) {
            $order->update(['payment_brand' => $user->pm_type]);
        }

        return response()->json([
            'data' => new OrderResource($order->fresh()->load(['items.product'])),
        ], 201);
    }
}
