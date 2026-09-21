<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReadyProductResource;
use App\Models\ReadyProduct;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/** Saytdagi "Tayyor mahsulotlar" bo'limi. */
class ReadyProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $items = QueryBuilder::for(
            ReadyProduct::query()->active()
                ->with(['category', 'images.file'])
                ->withCount('approvedReviews')
                ->withAvg('approvedReviews as approved_reviews_avg_rating', 'rating')
        )
            ->allowedFilters(
                AllowedFilter::exact('category_id'),
                AllowedFilter::callback('search', fn ($q, $v) => $q->where(fn ($w) => $w
                    ->where('name_uz', 'ilike', "%{$v}%")->orWhere('name_ru', 'ilike', "%{$v}%")->orWhere('name_en', 'ilike', "%{$v}%"))),
                AllowedFilter::callback('price_from', fn ($q, $v) => $q->where('price', '>=', $v)),
                AllowedFilter::callback('price_to', fn ($q, $v) => $q->where('price', '<=', $v)),
            )
            ->allowedSorts('price', 'created_at', 'sort')
            ->defaultSort('sort', '-id')
            ->paginate($this->perPage($request));

        return ApiResponse::paginated($items, ReadyProductResource::class);
    }

    public function show(string $slug): JsonResponse
    {
        $item = ReadyProduct::query()->active()
            ->with(['category', 'images.file'])
            ->withCount('approvedReviews')
            ->withAvg('approvedReviews as approved_reviews_avg_rating', 'rating')
            ->where('slug', $slug)
            ->firstOrFail();

        return ApiResponse::item(new ReadyProductResource($item));
    }
}
