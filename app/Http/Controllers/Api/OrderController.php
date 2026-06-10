<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\Product;
use App\Models\PromoCode;
use App\Models\Subscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
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
        $order = DB::transaction(function () use ($request) {
            $subtotal = 0;
            $lineItems = [];

            foreach ($request->items as $item) {
                $product = Product::findOrFail($item['product_id']);
                $price = $item['cycle'] === 'yearly'
                    ? (float) $product->price_yearly
                    : (float) $product->price_monthly;

                $subtotal += $price;
                $lineItems[] = [
                    'product' => $product,
                    'cycle' => $item['cycle'],
                    'price' => $price,
                ];
            }

            $total = $subtotal;

            if ($request->filled('promo_code')) {
                $promo = PromoCode::where('code', $request->promo_code)->first();

                if ($promo && $this->isPromoValid($promo, $subtotal)) {
                    $total = $this->applyDiscount($promo, $subtotal);
                    $promo->increment('uses_count');
                }
            }

            $order = Order::create([
                'user_id' => auth()->id(),
                'total' => $total,
                'billing_name' => $request->billing_name,
                'billing_address' => $request->billing_address,
                'stripe_payment_intent' => $request->stripe_payment_intent,
                'card_last4' => $request->card_last4,
                'status' => $request->stripe_payment_intent ? 'paid' : 'pending',
            ]);

            foreach ($lineItems as $line) {
                $order->items()->create([
                    'product_id' => $line['product']->id,
                    'cycle' => $line['cycle'],
                    'price' => $line['price'],
                ]);

                if ($order->status === 'paid') {
                    $nextBilling = $line['cycle'] === 'yearly'
                        ? now()->addYear()
                        : now()->addMonth();

                    Subscription::create([
                        'user_id' => auth()->id(),
                        'order_id' => $order->id,
                        'product_id' => $line['product']->id,
                        'cycle' => $line['cycle'],
                        'price' => $line['price'],
                        'status' => 'active',
                        'start_date' => now()->toDateString(),
                        'next_billing' => $nextBilling->toDateString(),
                    ]);
                }
            }

            return $order->load(['items.product']);
        });

        return response()->json([
            'data' => new OrderResource($order),
        ], 201);
    }

    private function isPromoValid(PromoCode $promo, float $amount): bool
    {
        if (! $promo->is_active) {
            return false;
        }

        if ($promo->expires_at && $promo->expires_at->isPast()) {
            return false;
        }

        if ($promo->max_uses !== null && $promo->uses_count >= $promo->max_uses) {
            return false;
        }

        if ($amount < (float) $promo->min_amount) {
            return false;
        }

        return true;
    }

    private function applyDiscount(PromoCode $promo, float $amount): float
    {
        if ($promo->type === 'fixed') {
            return max(0, $amount - (float) $promo->value);
        }

        return max(0, $amount - ($amount * ((float) $promo->value / 100)));
    }
}
