<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReadyProduct\StoreReadyProductRequest;
use App\Http\Resources\ReadyProductResource;
use App\Models\ReadyProduct;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/** Tayyor mahsulotlar: rasm bilan kiritiladi, konstruktordan mustaqil. */
class ReadyProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $items = QueryBuilder::for(ReadyProduct::query()->with(['category', 'images.file']))
            ->allowedFilters(
                AllowedFilter::exact('status'),
                AllowedFilter::exact('category_id'),
                AllowedFilter::callback('search', fn ($q, $v) => $q->where(fn ($w) => $w
                    ->where('name_uz', 'ilike', "%{$v}%")->orWhere('name_ru', 'ilike', "%{$v}%")->orWhere('name_en', 'ilike', "%{$v}%"))),
            )
            ->allowedSorts('id', 'price', 'sort', 'created_at')
            ->defaultSort('sort', '-id')
            ->paginate($this->perPage($request));

        return ApiResponse::paginated($items, ReadyProductResource::class);
    }

    public function show(ReadyProduct $readyProduct): JsonResponse
    {
        return ApiResponse::item(new ReadyProductResource($readyProduct->load(['category', 'images.file'])));
    }

    public function store(StoreReadyProductRequest $request): JsonResponse
    {
        $item = ReadyProduct::query()->create($this->payload($request));
        $this->syncImages($item, $request->input('image_ids', []));

        return ApiResponse::created(new ReadyProductResource($item->load(['category', 'images.file'])));
    }

    public function update(StoreReadyProductRequest $request, ReadyProduct $readyProduct): JsonResponse
    {
        $readyProduct->update($this->payload($request, $readyProduct));
        if ($request->has('image_ids')) {
            $this->syncImages($readyProduct, $request->input('image_ids', []));
        }

        return ApiResponse::item(new ReadyProductResource($readyProduct->load(['category', 'images.file'])));
    }

    public function destroy(ReadyProduct $readyProduct): JsonResponse
    {
        $readyProduct->delete();

        return ApiResponse::noContent();
    }

    /** @return array<string, mixed> */
    private function payload(StoreReadyProductRequest $request, ?ReadyProduct $existing = null): array
    {
        $data = $request->safe()->except('image_ids');
        if (empty($data['slug'])) {
            $base = Str::slug((string) ($data['name_uz'] ?? $existing?->name_uz ?? 'mahsulot'));
            $data['slug'] = $existing && $existing->slug ? $existing->slug : $base.'-'.Str::lower(Str::random(4));
        }

        return $data;
    }

    /** @param  array<int, int>  $fileIds */
    private function syncImages(ReadyProduct $item, array $fileIds): void
    {
        $item->images()->delete();
        foreach (array_values($fileIds) as $i => $fileId) {
            $item->images()->create(['file_id' => $fileId, 'sort' => $i]);
        }
    }
}
