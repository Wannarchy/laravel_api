<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminOrderController extends Controller
{
    public function index(): JsonResponse
    {
        $orders = Order::with(['items.product', 'user'])
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'data' => OrderResource::collection($orders),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $order = Order::with(['items.product', 'user'])->find($id);

        if (! $order) {
            return response()->json(['message' => 'Commande introuvable.'], 404);
        }

        return response()->json([
            'data' => new OrderResource($order),
        ]);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $order = Order::find($id);

        if (! $order) {
            return response()->json(['message' => 'Commande introuvable.'], 404);
        }

        $validated = $request->validate([
            'status' => ['required', 'string', 'max:30'],
        ]);

        $order->update(['status' => $validated['status']]);

        return response()->json([
            'data' => new OrderResource($order->fresh()->load(['items.product'])),
            'message' => 'Statut de la commande mis à jour.',
        ]);
    }
}
