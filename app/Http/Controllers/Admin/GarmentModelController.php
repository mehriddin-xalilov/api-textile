<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Catalog\StoreGarmentModelRequest;
use App\Http\Resources\GarmentModelResource;
use App\Models\GarmentModel;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/** 3D kiyim modellari (GLB): yuklash, faol/faol emas, bosma zonalar. */
class GarmentModelController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $items = QueryBuilder::for(GarmentModel::query()->with(['file', 'thumbnail'])->withCount('products'))
            ->allowedFilters(AllowedFilter::partial('search', 'name_uz'), AllowedFilter::exact('status'))
            ->allowedSorts('id', 'sort', 'name_uz')
            ->defaultSort('sort', 'id')
            ->paginate($this->perPage($request));

        return ApiResponse::paginated($items, GarmentModelResource::class);
    }

    public function show(GarmentModel $garmentModel): JsonResponse
    {
        return ApiResponse::item(new GarmentModelResource($garmentModel->load(['file', 'thumbnail'])->loadCount('products')));
    }

    public function store(StoreGarmentModelRequest $request): JsonResponse
    {
        return ApiResponse::created(new GarmentModelResource(GarmentModel::query()->create($request->validated())->load(['file', 'thumbnail'])));
    }

    public function update(StoreGarmentModelRequest $request, GarmentModel $garmentModel): JsonResponse
    {
        $garmentModel->update($request->validated());

        return ApiResponse::item(new GarmentModelResource($garmentModel->load(['file', 'thumbnail'])));
    }

    public function destroy(GarmentModel $garmentModel): JsonResponse
    {
        abort_if($garmentModel->products()->exists(), 422, "Bu modelga mahsulotlar bog'langan, avval ularni ajrating.");
        $garmentModel->delete();

        return ApiResponse::noContent();
    }
}
