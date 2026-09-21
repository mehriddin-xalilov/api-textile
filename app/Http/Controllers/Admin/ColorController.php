<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Catalog\StoreColorRequest;
use App\Http\Resources\ColorResource;
use App\Models\Color;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ColorController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $colors = QueryBuilder::for(Color::class)
            ->allowedFilters(AllowedFilter::partial('search', 'name_uz'), AllowedFilter::exact('status'))
            ->allowedSorts('id', 'sort', 'name_uz')
            ->defaultSort('sort', 'id')
            ->paginate($this->perPage($request));

        return ApiResponse::paginated($colors, ColorResource::class);
    }

    public function store(StoreColorRequest $request): JsonResponse
    {
        return ApiResponse::created(new ColorResource(Color::query()->create($request->validated())));
    }

    public function update(StoreColorRequest $request, Color $color): JsonResponse
    {
        $color->update($request->validated());

        return ApiResponse::item(new ColorResource($color));
    }

    public function destroy(Color $color): JsonResponse
    {
        $color->delete();

        return ApiResponse::noContent();
    }
}
