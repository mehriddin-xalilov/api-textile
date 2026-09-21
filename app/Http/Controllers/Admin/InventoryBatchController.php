<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Inventory\ReceiveInventoryBatch;
use App\Enums\BatchStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Inventory\StoreInventoryBatchRequest;
use App\Http\Resources\InventoryBatchResource;
use App\Models\InventoryBatch;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/** Kelgan partiyalar (Xitoy / Turkiya). Draft → Received. */
class InventoryBatchController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $batches = QueryBuilder::for(InventoryBatch::class)
            ->allowedIncludes('creator')
            ->allowedFilters(
                AllowedFilter::partial('search', 'number'),
                AllowedFilter::exact('status'),
                AllowedFilter::exact('supplier_country'))
            ->allowedSorts('id', 'arrived_at', 'received_at', 'created_at')
            ->defaultSort('-id')
            ->withCount('items')
            ->withSum('items', 'quantity')
            ->paginate($this->perPage($request));

        return ApiResponse::paginated($batches, InventoryBatchResource::class);
    }

    public function show(InventoryBatch $batch): JsonResponse
    {
        $batch->load(['creator', 'items.variant.product', 'items.variant.productColor.color', 'items.variant.size'])
            ->loadCount('items')->loadSum('items', 'quantity');

        return ApiResponse::item(new InventoryBatchResource($batch));
    }

    public function store(StoreInventoryBatchRequest $request): JsonResponse
    {
        $batch = DB::transaction(function () use ($request) {
            $batch = InventoryBatch::query()->create($request->safe()->except('items') + [
                'number' => InventoryBatch::nextNumber(),
                'status' => BatchStatus::Draft,
                'created_by' => $request->user()->id,
            ]);
            $batch->items()->createMany($request->input('items', []));

            return $batch;
        });

        return ApiResponse::created(new InventoryBatchResource($batch->load('items')));
    }

    public function update(StoreInventoryBatchRequest $request, InventoryBatch $batch): JsonResponse
    {
        abort_if($batch->status === BatchStatus::Received, 422, "Qabul qilingan partiyani o'zgartirib bo'lmaydi.");

        DB::transaction(function () use ($request, $batch) {
            $batch->update($request->safe()->except('items'));
            if ($request->has('items')) {
                $batch->items()->delete();
                $batch->items()->createMany($request->input('items', []));
            }
        });

        return ApiResponse::item(new InventoryBatchResource($batch->load('items')));
    }

    public function destroy(InventoryBatch $batch): JsonResponse
    {
        abort_if($batch->status === BatchStatus::Received, 422, "Qabul qilingan partiyani o'chirib bo'lmaydi.");
        $batch->delete();

        return ApiResponse::noContent();
    }

    /** POST /inventory-batches/{batch}/receive — omborga kiritish. */
    public function receive(Request $request, InventoryBatch $batch, ReceiveInventoryBatch $action): JsonResponse
    {
        $action->handle($batch, $request->user()->id);

        return $this->show($batch->fresh());
    }
}
