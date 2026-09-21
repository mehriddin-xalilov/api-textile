<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StockMovementType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Catalog\AdjustStockRequest;
use App\Http\Resources\ProductVariantResource;
use App\Models\ProductVariant;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/** Ombor qoldiqlari: variant (mahsulot + rang + razmer) kesimida. */
class ProductVariantController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $variants = QueryBuilder::for(ProductVariant::query()->with(['product', 'productColor.color', 'size']))
            ->allowedFilters(
                AllowedFilter::exact('product_id'),
                AllowedFilter::exact('product_color_id'),
                AllowedFilter::exact('size_id'),
                AllowedFilter::partial('sku'),
                AllowedFilter::callback('in_stock', fn ($q, $v) => $v ? $q->whereColumn('quantity', '>', 'reserved') : $q),
                AllowedFilter::callback('low_stock', fn ($q, $v) => $q->whereRaw('quantity - reserved <= ?', [(int) $v])))
            ->allowedSorts('id', 'sku', 'quantity', 'reserved')
            ->defaultSort('product_id', 'product_color_id', 'size_id')
            ->paginate($this->perPage($request));

        return ApiResponse::paginated($variants, ProductVariantResource::class);
    }

    /** POST /variants/{variant}/adjust — qo'lda tuzatish (+/-). */
    public function adjust(AdjustStockRequest $request, ProductVariant $variant): JsonResponse
    {
        DB::transaction(function () use ($request, $variant) {
            $variant = ProductVariant::query()->lockForUpdate()->findOrFail($variant->id);
            $delta = (int) $request->quantity;

            if ($variant->quantity + $delta < $variant->reserved) {
                throw ValidationException::withMessages(['quantity' => "Band qilingan miqdordan kam bo'lishi mumkin emas."]);
            }

            $variant->increment('quantity', $delta);
            $variant->movements()->create([
                'type' => StockMovementType::Adjust,
                'quantity' => $delta,
                'created_by' => $request->user()->id,
            ]);
        });

        return ApiResponse::item(new ProductVariantResource($variant->fresh(['product', 'productColor.color', 'size'])));
    }
}
