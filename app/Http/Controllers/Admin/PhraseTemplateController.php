<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Catalog\StorePhraseTemplateRequest;
use App\Http\Resources\PhraseTemplateResource;
use App\Models\PhraseTemplate;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/** Trend so'zlar / gaplar (konstruktor matn shablonlari). */
class PhraseTemplateController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $items = QueryBuilder::for(PhraseTemplate::class)
            ->allowedFilters(AllowedFilter::partial('search', 'text'), AllowedFilter::exact('status'), AllowedFilter::exact('category'))
            ->allowedSorts('id', 'sort', 'text')
            ->defaultSort('sort', 'id')
            ->paginate($this->perPage($request));

        return ApiResponse::paginated($items, PhraseTemplateResource::class);
    }

    public function store(StorePhraseTemplateRequest $request): JsonResponse
    {
        return ApiResponse::created(new PhraseTemplateResource(PhraseTemplate::query()->create($request->validated())));
    }

    public function update(StorePhraseTemplateRequest $request, PhraseTemplate $phrase): JsonResponse
    {
        $phrase->update($request->validated());

        return ApiResponse::item(new PhraseTemplateResource($phrase));
    }

    public function destroy(PhraseTemplate $phrase): JsonResponse
    {
        $phrase->delete();

        return ApiResponse::noContent();
    }
}
