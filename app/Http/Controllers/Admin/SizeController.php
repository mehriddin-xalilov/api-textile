<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Catalog\StoreSizeRequest;
use App\Http\Resources\SizeResource;
use App\Models\Size;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class SizeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $sizes = QueryBuilder::for(Size::class)
            ->allowedFilters(AllowedFilter::partial('search', 'name'), AllowedFilter::exact('status'))
            ->allowedSorts('id', 'sort', 'name')
            ->defaultSort('sort', 'id')
            ->paginate($this->perPage($request));

        return ApiResponse::paginated($sizes, SizeResource::class);
    }

    public function store(StoreSizeRequest $request): JsonResponse
    {
        return ApiResponse::created(new SizeResource(Size::query()->create($request->validated())));
    }

    public function update(StoreSizeRequest $request, Size $size): JsonResponse
    {
        $size->update($request->validated());

        return ApiResponse::item(new SizeResource($size));
    }

    public function destroy(Size $size): JsonResponse
    {
        $size->delete();

        return ApiResponse::noContent();
    }
}
