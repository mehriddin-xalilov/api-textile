<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Cms\StoreBannerRequest;
use App\Http\Resources\BannerResource;
use App\Models\Banner;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class BannerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $items = QueryBuilder::for(Banner::query()->with('image'))
            ->allowedFilters(AllowedFilter::partial('search', 'title_uz'), AllowedFilter::exact('status'))
            ->allowedSorts('id', 'sort')->defaultSort('sort', 'id')
            ->paginate($this->perPage($request));

        return ApiResponse::paginated($items, BannerResource::class);
    }

    public function store(StoreBannerRequest $request): JsonResponse
    {
        return ApiResponse::created(new BannerResource(Banner::query()->create($request->validated())->load('image')));
    }

    public function update(StoreBannerRequest $request, Banner $banner): JsonResponse
    {
        $banner->update($request->validated());

        return ApiResponse::item(new BannerResource($banner->load('image')));
    }

    public function destroy(Banner $banner): JsonResponse
    {
        $banner->delete();

        return ApiResponse::noContent();
    }
}
