<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Catalog\StoreProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $products = QueryBuilder::for(Product::class)
            ->allowedIncludes('category', 'colors', 'colors.color', 'colors.frontImage', 'garmentModel')
            ->allowedFilters(
                AllowedFilter::partial('search', 'name_uz'),
                AllowedFilter::exact('status'),
                AllowedFilter::exact('category_id'),
                AllowedFilter::exact('origin_country'),
                AllowedFilter::exact('gender'),
                AllowedFilter::exact('type'),
                AllowedFilter::callback('in_stock', fn ($q, $v) => $v ? $q->whereHas('variants', fn ($w) => $w->whereColumn('quantity', '>', 'reserved')) : $q))
            ->allowedSorts('id', 'sort', 'name_uz', 'base_price', 'created_at')
            ->defaultSort('sort', '-id')
            ->withAvailableStock()
            ->paginate($this->perPage($request));

        return ApiResponse::paginated($products, ProductResource::class);
    }

    public function show(Product $product): JsonResponse
    {
        $product = Product::query()
            ->withAvailableStock()
            ->with([
                'category', 'garmentModel.file', 'garmentModel.thumbnail',
                'colors.color', 'colors.frontImage', 'colors.backImage', 'colors.leftImage', 'colors.rightImage',
                'colors.variants.size',
                'printAreas',
            ])
            ->findOrFail($product->id);

        return ApiResponse::item(new ProductResource($product));
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        return ApiResponse::created(new ProductResource(Product::query()->create($request->validated())));
    }

    public function update(StoreProductRequest $request, Product $product): JsonResponse
    {
        $product->update($request->validated());

        return ApiResponse::item(new ProductResource($product));
    }

    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return ApiResponse::noContent();
    }
}
