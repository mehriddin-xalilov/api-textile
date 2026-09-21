<?php

namespace App\Http\Controllers\Client;

use App\Enums\DesignStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Client\Designs\StoreDesignRequest;
use App\Http\Resources\DesignResource;
use App\Models\Design;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Foydalanuvchining o'z dizaynlari (konstruktor saqlashi). */
class DesignController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $designs = $request->user()->designs()
            ->with(['preview', 'product', 'productColor.color'])
            ->where('status', '!=', DesignStatus::Archived)
            ->latest('updated_at')
            ->paginate($this->perPage($request));

        return ApiResponse::paginated($designs, DesignResource::class);
    }

    public function show(Request $request, Design $design): JsonResponse
    {
        $this->authorizeOwner($request, $design);

        return ApiResponse::item(new DesignResource($design->load(['preview', 'printFile', 'product.printAreas', 'productColor.color', 'productColor.frontImage', 'productColor.backImage', 'productColor.leftImage', 'productColor.rightImage'])));
    }

    public function store(StoreDesignRequest $request): JsonResponse
    {
        // canvas: validated() faqat qoidali kalitlarni qoldiradi — konstruktorning to'liq JSON'ini saqlaymiz (tekshiruvdan o'tgan).
        $design = $request->user()->designs()->create(['canvas' => $request->input('canvas')] + $request->validated() + ['status' => DesignStatus::Draft]);

        return ApiResponse::created(new DesignResource($design->load(['preview', 'productColor.color', 'product.printAreas'])));
    }

    public function update(StoreDesignRequest $request, Design $design): JsonResponse
    {
        $this->authorizeOwner($request, $design);
        $design->update(['canvas' => $request->input('canvas')] + $request->validated());

        return ApiResponse::item(new DesignResource($design->load(['preview', 'productColor.color', 'product.printAreas'])));
    }

    public function destroy(Request $request, Design $design): JsonResponse
    {
        $this->authorizeOwner($request, $design);
        $design->update(['status' => DesignStatus::Archived]);

        return ApiResponse::noContent();
    }

    private function authorizeOwner(Request $request, Design $design): void
    {
        abort_unless($design->user_id === $request->user()->id, 404);
    }
}
