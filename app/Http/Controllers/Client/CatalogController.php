<?php

namespace App\Http\Controllers\Client;

use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ClipartResource;
use App\Http\Resources\DesignResource;
use App\Http\Resources\PhraseTemplateResource;
use App\Http\Resources\ProductResource;
use App\Models\Category;
use App\Models\Clipart;
use App\Models\Design;
use App\Models\PhraseTemplate;
use App\Models\Product;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/** Ommaviy katalog: faqat faol mahsulotlar. */
class CatalogController extends Controller
{
    public function categories(): JsonResponse
    {
        $categories = Category::query()
            ->where('status', Status::Active)
            ->whereNull('parent_id')
            ->with(['image', 'children' => fn ($q) => $q->where('status', Status::Active)->orderBy('sort')])
            ->orderBy('sort')
            ->get();

        return ApiResponse::collection($categories, CategoryResource::class);
    }

    public function products(Request $request): JsonResponse
    {
        $query = Product::query()
            ->active()
            ->withAvailableStock()
            ->withCount('approvedReviews')
            ->withAvg('approvedReviews as approved_reviews_avg_rating', 'rating')
            ->with(['category', 'garmentModel.file', 'garmentModel.thumbnail', 'colors' => fn ($q) => $q->where('status', Status::Active)->with(['color', 'frontImage'])]);

        $products = QueryBuilder::for($query)
            ->allowedFilters(
                AllowedFilter::exact('category_id'),
                AllowedFilter::exact('gender'),
                AllowedFilter::exact('type'),
                AllowedFilter::callback('search', fn ($q, $v) => $q->where(fn ($w) => $w
                    ->where('name_uz', 'ilike', "%{$v}%")->orWhere('name_ru', 'ilike', "%{$v}%")->orWhere('name_en', 'ilike', "%{$v}%"))),
                AllowedFilter::callback('price_from', fn ($q, $v) => $q->where('base_price', '>=', $v)),
                AllowedFilter::callback('price_to', fn ($q, $v) => $q->where('base_price', '<=', $v)),
            )
            ->allowedSorts('base_price', 'created_at', 'sort')
            ->defaultSort('sort', '-id')
            ->paginate($this->perPage($request));

        return ApiResponse::paginated($products, ProductResource::class);
    }

    /** Tayyor logolar: faol, kategoriya bo'yicha. */
    public function cliparts(Request $request): JsonResponse
    {
        $items = Clipart::query()->with('file')
            ->where('status', Status::Active)
            ->when($request->query('category'), fn ($q, $c) => $q->where('category', $c))
            ->orderBy('sort')->orderBy('id')
            ->get();

        return ApiResponse::collection($items, ClipartResource::class);
    }

    /** Trend so'zlar: faol, kategoriya bo'yicha. */
    public function phrases(Request $request): JsonResponse
    {
        $items = PhraseTemplate::query()
            ->where('status', Status::Active)
            ->when($request->query('category'), fn ($q, $c) => $q->where('category', $c))
            ->orderBy('sort')->orderBy('id')
            ->get();

        return ApiResponse::collection($items, PhraseTemplateResource::class);
    }

    /** Tayyor dizaynlar (shablonlar): saytdagi "Tayyor mahsulotlar" bo'limi. */
    public function templates(Request $request): JsonResponse
    {
        $items = Design::query()->where('is_template', true)
            ->withCount('approvedReviews')
            ->withAvg('approvedReviews as approved_reviews_avg_rating', 'rating')
            ->with(['preview', 'photo', 'product.garmentModel.file', 'productColor.color', 'productColor.variants.size'])
            ->whereHas('product', fn ($q) => $q->where('status', Status::Active))
            ->when($request->query('product_id'), fn ($q, $id) => $q->where('product_id', $id))
            ->orderBy('sort')->orderByDesc('id')
            ->get();

        return ApiResponse::collection($items, DesignResource::class);
    }

    public function template(Design $design): JsonResponse
    {
        abort_unless($design->is_template, 404);

        $design->loadCount('approvedReviews')->loadAvg('approvedReviews as approved_reviews_avg_rating', 'rating');

        return ApiResponse::item(new DesignResource($design->load(['preview', 'photo', 'product.garmentModel.file', 'productColor.color', 'productColor.variants.size'])));
    }

    public function product(string $slug): JsonResponse
    {
        $product = Product::query()->active()
            ->withAvailableStock()
            ->withCount('approvedReviews')
            ->withAvg('approvedReviews as approved_reviews_avg_rating', 'rating')
            ->with([
                'category', 'garmentModel.file', 'garmentModel.thumbnail',
                'colors' => fn ($q) => $q->where('status', Status::Active)
                    ->with(['color', 'frontImage', 'backImage', 'leftImage', 'rightImage', 'variants' => fn ($v) => $v->with('size')->orderBy('size_id')]),
                'printAreas',
            ])
            ->where('slug', $slug)
            ->firstOrFail();

        return ApiResponse::item(new ProductResource($product));
    }
}
