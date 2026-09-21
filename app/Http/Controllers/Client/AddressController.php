<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\Client\Addresses\StoreAddressRequest;
use App\Http\Resources\UserAddressResource;
use App\Models\UserAddress;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Mijozning manzillari (faqat o'ziniki). */
class AddressController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $items = UserAddress::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('is_default')->orderByDesc('id')
            ->get();

        return ApiResponse::collection($items, UserAddressResource::class);
    }

    public function store(StoreAddressRequest $request): JsonResponse
    {
        $address = DB::transaction(function () use ($request) {
            $data = $request->validated();
            $data['user_id'] = $request->user()->id;
            // Birinchi manzil doim asosiy bo'ladi
            $data['is_default'] = (bool) ($data['is_default'] ?? false)
                || ! UserAddress::query()->where('user_id', $request->user()->id)->exists();

            $address = UserAddress::query()->create($data);
            $this->syncDefault($address);

            return $address;
        });

        return ApiResponse::created(new UserAddressResource($address));
    }

    public function update(StoreAddressRequest $request, UserAddress $address): JsonResponse
    {
        abort_if($address->user_id !== $request->user()->id, 403);

        DB::transaction(function () use ($request, $address) {
            $address->update($request->validated());
            $this->syncDefault($address);
        });

        return ApiResponse::item(new UserAddressResource($address->fresh()));
    }

    public function destroy(Request $request, UserAddress $address): JsonResponse
    {
        abort_if($address->user_id !== $request->user()->id, 403);
        $wasDefault = $address->is_default;
        $address->delete();

        // Asosiy manzil o'chirilsa — eng oxirgisi asosiy bo'ladi
        if ($wasDefault) {
            $next = UserAddress::query()->where('user_id', $request->user()->id)->orderByDesc('id')->first();
            $next?->update(['is_default' => true]);
        }

        return ApiResponse::noContent();
    }

    /** Bitta manzil asosiy bo'lib qoladi. */
    private function syncDefault(UserAddress $address): void
    {
        if (! $address->is_default) {
            return;
        }

        UserAddress::query()
            ->where('user_id', $address->user_id)
            ->whereKeyNot($address->id)
            ->update(['is_default' => false]);
    }
}
