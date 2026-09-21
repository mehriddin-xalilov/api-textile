<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Catalog\StoreClipartRequest;
use App\Http\Resources\ClipartResource;
use App\Models\Clipart;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/** Tayyor logolar kutubxonasi (konstruktor uchun). */
class ClipartController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $items = QueryBuilder::for(Clipart::query()->with('file'))
            ->allowedFilters(AllowedFilter::partial('search', 'name_uz'), AllowedFilter::exact('status'), AllowedFilter::exact('category'))
            ->allowedSorts('id', 'sort', 'name_uz')
            ->defaultSort('sort', 'id')
            ->paginate($this->perPage($request));

        return ApiResponse::paginated($items, ClipartResource::class);
    }

    public function store(StoreClipartRequest $request): JsonResponse
    {
        return ApiResponse::created(new ClipartResource(Clipart::query()->create($request->validated())->load('file')));
    }

    public function update(StoreClipartRequest $request, Clipart $clipart): JsonResponse
    {
        $clipart->update($request->validated());

        return ApiResponse::item(new ClipartResource($clipart->load('file')));
    }

    public function destroy(Clipart $clipart): JsonResponse
    {
        $clipart->delete();

        return ApiResponse::noContent();
    }
}
