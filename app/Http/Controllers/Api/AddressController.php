<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAddressRequest;
use App\Http\Resources\AddressResource;
use App\Models\UserAddress;
use Illuminate\Http\JsonResponse;

class AddressController extends Controller
{
    public function index(): JsonResponse
    {
        $addresses = UserAddress::where('user_id', auth()->id())
            ->orderByDesc('is_default')
            ->orderByDesc('is_default_shipping')
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'data' => AddressResource::collection($addresses),
        ]);
    }

    public function store(StoreAddressRequest $request): JsonResponse
    {
        $userId = (int) auth()->id();
        $data = $this->prepareAddressData($request->validated(), $userId, true);

        $address = UserAddress::create([
            ...$data,
            'user_id' => $userId,
            'pays' => $data['pays'] ?? 'France',
        ]);

        return response()->json(['data' => new AddressResource($address)], 201);
    }

    public function update(StoreAddressRequest $request, int $id): JsonResponse
    {
        $address = UserAddress::where('user_id', auth()->id())->find($id);

        if (! $address) {
            return response()->json(['message' => 'Adresse introuvable.'], 404);
        }

        $data = $this->prepareAddressData($request->validated(), (int) auth()->id(), false);
        $address->update($data);

        return response()->json(['data' => new AddressResource($address->fresh())]);
    }

    public function destroy(int $id): JsonResponse
    {
        $userId = (int) auth()->id();
        $address = UserAddress::where('user_id', $userId)->find($id);

        if (! $address) {
            return response()->json(['message' => 'Adresse introuvable.'], 404);
        }

        $wasBillingDefault = (bool) $address->is_default;
        $wasShippingDefault = (bool) $address->is_default_shipping;

        $address->delete();

        if ($wasBillingDefault) {
            $next = UserAddress::where('user_id', $userId)
                ->whereIn('usage_type', ['billing', 'both'])
                ->orderByDesc('created_at')
                ->first();

            if ($next) {
                $next->update(['is_default' => true]);
            }
        }

        if ($wasShippingDefault) {
            $next = UserAddress::where('user_id', $userId)
                ->whereIn('usage_type', ['shipping', 'both'])
                ->orderByDesc('created_at')
                ->first();

            if ($next) {
                $next->update(['is_default_shipping' => true]);
            }
        }

        return response()->json(['message' => 'Adresse supprimée.']);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function prepareAddressData(array $data, int $userId, bool $isCreate): array
    {
        $usageType = $data['usage_type'] ?? 'both';
        $data['usage_type'] = $usageType;

        $canBill = $this->canUseForBilling($usageType);
        $canShip = $this->canUseForShipping($usageType);

        if (! $canBill) {
            $data['is_default'] = false;
        }

        if (! $canShip) {
            $data['is_default_shipping'] = false;
        }

        $isFirst = $isCreate && UserAddress::where('user_id', $userId)->count() === 0;

        if ($isFirst && $canBill) {
            $data['is_default'] = true;
        }

        if ($isFirst && $canShip) {
            $data['is_default_shipping'] = true;
        }

        if (! empty($data['is_default']) && $canBill) {
            UserAddress::where('user_id', $userId)->update(['is_default' => false]);
        }

        if (! empty($data['is_default_shipping']) && $canShip) {
            UserAddress::where('user_id', $userId)->update(['is_default_shipping' => false]);
        }

        return $data;
    }

    private function canUseForBilling(string $usageType): bool
    {
        return in_array($usageType, ['billing', 'both'], true);
    }

    private function canUseForShipping(string $usageType): bool
    {
        return in_array($usageType, ['shipping', 'both'], true);
    }
}
