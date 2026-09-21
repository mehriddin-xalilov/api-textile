<?php

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Enums\StockMovementType;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Buyurtma holatini o'zgartirish. Holat mashinasi OrderStatus::transitions() da.
 *  - cancelled → band qilingan miqdor bo'shatiladi
 *  - shipped   → ombordan chiqim (quantity va reserved kamayadi)
 */
final class ChangeOrderStatus
{
    public function handle(Order $order, OrderStatus $to, ?string $comment = null, ?int $userId = null): Order
    {
        $from = $order->status;

        if (! $from->canTransitionTo($to)) {
            throw ValidationException::withMessages([
                'status' => "{$from->label()} → {$to->label()} o'tish mumkin emas.",
            ]);
        }

        return DB::transaction(function () use ($order, $from, $to, $comment, $userId) {
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);

            match ($to) {
                OrderStatus::Cancelled => $this->releaseStock($order, $userId),
                OrderStatus::Shipped => $this->consumeStock($order, $userId),
                default => null,
            };

            $timestamps = match ($to) {
                OrderStatus::Confirmed => ['confirmed_at' => now()],
                OrderStatus::Shipped => ['shipped_at' => now()],
                OrderStatus::Delivered => ['delivered_at' => now()],
                OrderStatus::Cancelled => ['cancelled_at' => now()],
                default => [],
            };

            $order->forceFill(['status' => $to] + $timestamps)->save();
            $order->histories()->create([
                'from_status' => $from, 'to_status' => $to, 'comment' => $comment, 'changed_by' => $userId,
            ]);

            return $order;
        });
    }

    private function releaseStock(Order $order, ?int $userId): void
    {
        $this->applyToVariants($order, $userId, StockMovementType::Release, function (ProductVariant $variant, int $qty) {
            $variant->decrement('reserved', $qty);
        });
    }

    private function consumeStock(Order $order, ?int $userId): void
    {
        $this->applyToVariants($order, $userId, StockMovementType::Out, function (ProductVariant $variant, int $qty) {
            $variant->update(['quantity' => $variant->quantity - $qty, 'reserved' => $variant->reserved - $qty]);
        });
    }

    private function applyToVariants(Order $order, ?int $userId, StockMovementType $type, callable $apply): void
    {
        $items = $order->items()->get(['product_variant_id', 'quantity']);

        $variants = ProductVariant::query()
            ->whereIn('id', $items->pluck('product_variant_id'))
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        $movements = [];
        foreach ($items as $item) {
            $apply($variants[$item->product_variant_id], $item->quantity);
            $movements[] = [
                'product_variant_id' => $item->product_variant_id,
                'type' => $type->value,
                'quantity' => $type === StockMovementType::Release ? $item->quantity : -$item->quantity,
                'reference_type' => $order->getMorphClass(),
                'reference_id' => $order->id,
                'created_by' => $userId,
                'created_at' => now(),
            ];
        }

        StockMovement::query()->insert($movements);
    }
}
