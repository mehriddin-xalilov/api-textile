<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Inventory\SyncProductVariants;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Catalog\StoreProductColorRequest;
use App\Http\Resources\ProductColorResource;
use App\Models\Product;
use App\Models\ProductColor;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/** Mahsulotga rang qo'shish → avtomatik variantlar (razmer bo'yicha SKU). */
class ProductColorController extends Controller
{
    public function index(Product $product): JsonResponse
    {
        $colors = $product->colors()->with(['color', 'frontImage', 'backImage', 'leftImage', 'rightImage', 'variants.size'])->get();

        return ApiResponse::collection($colors, ProductColorResource::class);
    }

    public function store(StoreProductColorRequest $request, Product $product, SyncProductVariants $sync): JsonResponse
    {
        $productColor = DB::transaction(function () use ($request, $product, $sync) {
            $pc = $product->colors()->create($request->safe()->except('size_ids'));
            $sync->handle($pc, $request->input('size_ids'));

            return $pc;
        });

        return ApiResponse::created(new ProductColorResource($productColor->load(['color', 'frontImage', 'backImage', 'leftImage', 'rightImage', 'variants.size'])));
    }

    public function update(StoreProductColorRequest $request, Product $product, ProductColor $color, SyncProductVariants $sync): JsonResponse
    {
        abort_unless($color->product_id === $product->id, 404);

        DB::transaction(function () use ($request, $color, $sync) {
            $color->update($request->safe()->except('size_ids'));
            if ($request->filled('size_ids')) {
                $sync->handle($color, $request->input('size_ids'));
            }
        });

        return ApiResponse::item(new ProductColorResource($color->load(['color', 'frontImage', 'backImage', 'leftImage', 'rightImage', 'variants.size'])));
    }

    public function destroy(Product $product, ProductColor $color): JsonResponse
    {
        abort_unless($color->product_id === $product->id, 404);
        abort_if($color->variants()->where('quantity', '>', 0)->exists(), 422, "Omborda qoldig'i bor rangni o'chirib bo'lmaydi.");
        $color->delete();

        return ApiResponse::noContent();
    }
}
