<?php

namespace App\Actions\Inventory;

use App\Enums\BatchStatus;
use App\Enums\StockMovementType;
use App\Models\InventoryBatch;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Xitoy/Turkiyadan kelgan partiyani omborga qabul qilish:
 * har bir variant miqdorini oshiradi va harakat yozuvini qoldiradi.
 */
final class ReceiveInventoryBatch
{
    public function handle(InventoryBatch $batch, ?int $userId = null): InventoryBatch
    {
        if ($batch->status === BatchStatus::Received) {
            throw ValidationException::withMessages(['status' => 'Partiya allaqachon qabul qilingan.']);
        }

        return DB::transaction(function () use ($batch, $userId) {
            $items = $batch->items()->get();

            if ($items->isEmpty()) {
                throw ValidationException::withMessages(['items' => "Partiyada mahsulot yo'q."]);
            }

            // Barcha variantlarni bitta so'rovda lock qilamiz (deadlock oldini olish uchun id bo'yicha tartiblab).
            $variants = ProductVariant::query()
                ->whereIn('id', $items->pluck('product_variant_id'))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $movements = [];
            $now = now();

            foreach ($items as $item) {
                $variants[$item->product_variant_id]->increment('quantity', $item->quantity);

                $movements[] = [
                    'product_variant_id' => $item->product_variant_id,
                    'type' => StockMovementType::In->value,
                    'quantity' => $item->quantity,
                    'reference_type' => $batch->getMorphClass(),
                    'reference_id' => $batch->id,
                    'created_by' => $userId,
                    'created_at' => $now,
                ];
            }

            StockMovement::query()->insert($movements);

            $batch->forceFill(['status' => BatchStatus::Received, 'received_at' => $now])->save();

            return $batch;
        });
    }
}
