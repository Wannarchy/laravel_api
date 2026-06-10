<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAddressRequest;
use App\Models\UserAddress;
use Illuminate\Http\JsonResponse;

class AddressController extends Controller
{
    public function index(): JsonResponse
    {
        $addresses = UserAddress::where('user_id', auth()->id())
            ->orderByDesc('is_default')
            ->get();

        return response()->json(['data' => $addresses]);
    }

    public function store(StoreAddressRequest $request): JsonResponse
    {
        if ($request->boolean('is_default')) {
            UserAddress::where('user_id', auth()->id())->update(['is_default' => false]);
        }

        $address = UserAddress::create([
            ...$request->validated(),
            'user_id' => auth()->id(),
            'pays' => $request->input('pays', 'France'),
        ]);

        return response()->json(['data' => $address], 201);
    }

    public function update(StoreAddressRequest $request, int $id): JsonResponse
    {
        $address = UserAddress::where('user_id', auth()->id())->find($id);

        if (! $address) {
            return response()->json(['message' => 'Adresse introuvable.'], 404);
        }

        if ($request->boolean('is_default')) {
            UserAddress::where('user_id', auth()->id())->update(['is_default' => false]);
        }

        $address->update($request->validated());

        return response()->json(['data' => $address->fresh()]);
    }

    public function destroy(int $id): JsonResponse
    {
        $address = UserAddress::where('user_id', auth()->id())->find($id);

        if (! $address) {
            return response()->json(['message' => 'Adresse introuvable.'], 404);
        }

        $address->delete();

        return response()->json(['message' => 'Adresse supprimée.']);
    }
}
