<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Catalog\StorePrintAreaRequest;
use App\Http\Resources\PrintAreaResource;
use App\Models\PrintArea;
use App\Models\Product;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class PrintAreaController extends Controller
{
    public function index(Product $product): JsonResponse
    {
        return ApiResponse::collection($product->printAreas()->orderBy('side')->get(), PrintAreaResource::class);
    }

    public function store(StorePrintAreaRequest $request, Product $product): JsonResponse
    {
        return ApiResponse::created(new PrintAreaResource($product->printAreas()->create($request->validated())));
    }

    public function update(StorePrintAreaRequest $request, Product $product, PrintArea $printArea): JsonResponse
    {
        abort_unless($printArea->product_id === $product->id, 404);
        $printArea->update($request->validated());

        return ApiResponse::item(new PrintAreaResource($printArea));
    }

    public function destroy(Product $product, PrintArea $printArea): JsonResponse
    {
        abort_unless($printArea->product_id === $product->id, 404);
        $printArea->delete();

        return ApiResponse::noContent();
    }
}
