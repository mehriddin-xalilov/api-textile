<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Review\UpdateReviewRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Review;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/** Izohlar moderatsiyasi: tasdiqlash / rad etish / javob yozish. */
class ReviewController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $items = QueryBuilder::for(Review::query()->with(['user', 'product']))
            ->allowedFilters(
                AllowedFilter::exact('status'),
                AllowedFilter::exact('product_id'),
                AllowedFilter::exact('design_id'),
                AllowedFilter::exact('rating'),
                AllowedFilter::callback('search', fn ($q, $v) => $q->where('comment', 'ilike', "%{$v}%")),
            )
            ->allowedSorts('id', 'rating', 'status')
            ->defaultSort('-id')
            ->paginate($this->perPage($request));

        return ApiResponse::paginated($items, ReviewResource::class);
    }

    public function update(UpdateReviewRequest $request, Review $review): JsonResponse
    {
        $review->update($request->validated());

        return ApiResponse::item(new ReviewResource($review->load(['user', 'product'])));
    }

    public function destroy(Review $review): JsonResponse
    {
        $review->delete();

        return ApiResponse::noContent();
    }
}
