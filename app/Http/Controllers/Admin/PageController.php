<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Cms\StorePageRequest;
use App\Http\Resources\PageResource;
use App\Models\Page;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class PageController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $items = QueryBuilder::for(Page::class)
            ->allowedFilters(AllowedFilter::partial('search', 'title_uz'), AllowedFilter::exact('status'))
            ->allowedSorts('id', 'sort')->defaultSort('sort', 'id')
            ->paginate($this->perPage($request));

        return ApiResponse::paginated($items, PageResource::class);
    }

    public function show(Page $page): JsonResponse
    {
        return ApiResponse::item(new PageResource($page));
    }

    public function store(StorePageRequest $request): JsonResponse
    {
        return ApiResponse::created(new PageResource(Page::query()->create($request->validated())));
    }

    public function update(StorePageRequest $request, Page $page): JsonResponse
    {
        $page->update($request->validated());

        return ApiResponse::item(new PageResource($page));
    }

    public function destroy(Page $page): JsonResponse
    {
        $page->delete();

        return ApiResponse::noContent();
    }
}
