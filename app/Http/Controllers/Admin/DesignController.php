<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\DesignResource;
use App\Models\Design;
use App\Models\OrderItem;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class DesignController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $designs = QueryBuilder::for(Design::query()->with(['user', 'preview', 'product.printAreas', 'productColor.color']))
            ->allowedFilters(AllowedFilter::exact('user_id'), AllowedFilter::exact('product_id'), AllowedFilter::exact('status'), AllowedFilter::exact('is_template'))
            ->allowedSorts('id', 'created_at', 'updated_at')
            ->defaultSort('-id')
            ->paginate($this->perPage($request));

        return ApiResponse::paginated($designs, DesignResource::class);
    }

    /** PUT /designs/{design} — admin konstruktorda dizaynni (mijoz buyurtmasini ham) tahrirlab saqlaydi. */
    public function update(Request $request, Design $design): JsonResponse
    {
        $data = $request->validate([
            'canvas' => ['required', 'array'],
            'product_color_id' => ['nullable', 'integer', Rule::exists('product_colors', 'id')->where('product_id', $design->product_id)],
            'preview_file_id' => ['nullable', 'integer', 'exists:files,id'],
            'photo_file_id' => ['nullable', 'integer', 'exists:files,id'],
            'print_file_id' => ['nullable', 'integer', 'exists:files,id'],
            'name' => ['nullable', 'string', 'max:255'],
        ]);
        $design->update($data);
        // Buyurtma pozitsiyalaridagi bosma faylni ham yangilaymiz (ishlab chiqarish eng so'nggisini olsin)
        if (! empty($data['print_file_id'])) {
            OrderItem::query()->where('design_id', $design->id)->update(['print_file_id' => $data['print_file_id']]);
        }

        return $this->show($design->fresh());
    }

    /** POST /designs/{design}/template {is_template, template_title, sort} — tayyor dizayn sifatida saytga chiqarish. */
    public function template(Request $request, Design $design): JsonResponse
    {
        $data = $request->validate([
            'is_template' => ['required', 'boolean'],
            'template_title' => ['nullable', 'string', 'max:120'],
            'sort' => ['nullable', 'integer', 'min:0'],
        ]);
        $design->update($data);

        return $this->show($design->fresh());
    }

    public function show(Design $design): JsonResponse
    {
        return ApiResponse::item(new DesignResource($design->load(['user', 'preview', 'photo', 'printFile', 'product.printAreas', 'productColor.color', 'productColor.frontImage', 'productColor.backImage', 'productColor.leftImage', 'productColor.rightImage'])));
    }
}
