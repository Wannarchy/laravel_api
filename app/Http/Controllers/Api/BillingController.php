<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCheckoutRequest;
use App\Services\OrderFulfillmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Laravel\Cashier\Cashier;

class BillingController extends Controller
{
    public function __construct(
        private OrderFulfillmentService $orderFulfillment,
    ) {}

    public function config(): JsonResponse
    {
        return response()->json([
            'data' => [
                'stripe_key' => config('cashier.key'),
            ],
        ]);
    }

    public function setupIntent(): JsonResponse
    {
        $user = auth()->user();
        $user->createOrGetStripeCustomer([
            'name' => trim($user->prenom.' '.$user->nom),
            'email' => $user->email,
        ]);

        $intent = $user->createSetupIntent();

        return response()->json([
            'data' => [
                'client_secret' => $intent->client_secret,
            ],
        ]);
    }

    public function checkout(StoreCheckoutRequest $request): JsonResponse
    {
        $user = auth()->user();

        try {
            $lineItems = $this->orderFulfillment->calculateLineItems($request->items);
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        try {
            $stripeLineItems = $lineItems->map(function (array $line) {
                return [
                    'price' => $this->orderFulfillment->stripePriceId($line['product'], $line['cycle']),
                    'quantity' => 1,
                ];
            })->all();
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $user->createOrGetStripeCustomer([
            'name' => $request->billing_name,
            'email' => $user->email,
        ]);

        $checkout = $user->checkout($stripeLineItems, [
            'success_url' => $request->success_url,
            'cancel_url' => $request->cancel_url,
            'metadata' => [
                'user_id' => (string) $user->id,
                'items' => json_encode($request->items),
                'billing_name' => $request->billing_name,
                'billing_address' => $request->billing_address,
                'promo_code' => $request->promo_code ?? '',
            ],
        ], [
            'mode' => 'subscription',
        ]);

        return response()->json([
            'data' => [
                'checkout_url' => $checkout->url,
                'session_id' => $checkout->id,
            ],
        ]);
    }

    public function checkoutSuccess(Request $request): JsonResponse
    {
        $request->validate([
            'session_id' => ['required', 'string'],
        ]);

        $session = Cashier::stripe()->checkout->sessions->retrieve(
            $request->session_id,
            ['expand' => ['subscription', 'customer']]
        );

        if ($session->payment_status !== 'paid') {
            return response()->json(['message' => 'Paiement non confirmé.'], 422);
        }

        $order = $this->orderFulfillment->fulfillCheckoutSession($session);

        if (! $order) {
            return response()->json(['message' => 'Commande introuvable ou déjà traitée.'], 404);
        }

        return response()->json([
            'data' => $order->load(['items.product']),
            'message' => 'Paiement confirmé.',
        ]);
    }
}
