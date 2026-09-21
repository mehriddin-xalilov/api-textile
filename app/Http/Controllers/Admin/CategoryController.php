<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Catalog\StoreCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class CategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $categories = QueryBuilder::for(Category::class)
            ->allowedIncludes('parent', 'children', 'image')
            ->allowedFilters(
                AllowedFilter::partial('search', 'name_uz'),
                AllowedFilter::exact('status'),
                AllowedFilter::exact('parent_id')->nullable())
            ->allowedSorts('id', 'sort', 'name_uz')
            ->defaultSort('sort', 'id')
            ->withCount('products')
            ->paginate($this->perPage($request));

        return ApiResponse::paginated($categories, CategoryResource::class);
    }

    public function show(Category $category): JsonResponse
    {
        return ApiResponse::item(new CategoryResource($category->load(['parent', 'image'])->loadCount('products')));
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        return ApiResponse::created(new CategoryResource(Category::query()->create($request->validated())));
    }

    public function update(StoreCategoryRequest $request, Category $category): JsonResponse
    {
        $category->update($request->validated());

        return ApiResponse::item(new CategoryResource($category));
    }

    public function destroy(Category $category): JsonResponse
    {
        abort_if($category->products()->exists(), 422, "Kategoriyada mahsulotlar bor, o'chirib bo'lmaydi.");
        $category->delete();

        return ApiResponse::noContent();
    }
}
