<?php

namespace App\Actions\Orders;

use App\Enums\DesignStatus;
use App\Enums\OrderStatus;
use App\Enums\StockMovementType;
use App\Models\Design;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\ReadyProduct;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Buyurtma yaratish: omborni tekshiradi, band qiladi, narxlarni snapshot qiladi.
 *
 * Qator ikki xil bo'ladi:
 *  - konstruktor mahsuloti: {product_variant_id, quantity, design_id?}
 *  - tayyor mahsulot:       {ready_product_id, quantity, size?}
 */
final class CreateOrder
{
    public function handle(User $user, array $items, array $delivery, ?string $paymentMethod = null): Order
    {
        return DB::transaction(function () use ($user, $items, $delivery, $paymentMethod) {
            $variantIds = collect($items)->pluck('product_variant_id')->filter()->unique()->sort()->values();

            $readyIds = collect($items)->pluck('ready_product_id')->filter()->unique()->sort()->values();
            $readyProducts = $readyIds->isEmpty()
                ? collect()
                : ReadyProduct::query()->whereIn('id', $readyIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');

            $variants = ProductVariant::query()
                ->with(['product', 'productColor.color', 'size'])
                ->whereIn('id', $variantIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $designIds = collect($items)->pluck('design_id')->filter()->unique();
            $designs = $designIds->isEmpty()
                ? collect()
                : Design::query()->whereIn('id', $designIds)
                    ->where(fn ($q) => $q->where('user_id', $user->id)->orWhere('is_template', true))
                    ->get()->keyBy('id');

            // Tayyor dizayn (shablon) sotib olinsa — mijoz uchun nusxa olinadi, shablonning o'zi o'zgarmaydi
            $cloned = [];
            foreach ($designs as $id => $design) {
                // Shablon har doim nusxalanadi: buyurtma keyinchalik shablon tahriridan ta'sirlanmasin
                if ($design->is_template) {
                    $copy = $design->replicate(['is_template', 'template_title', 'sort']);
                    $copy->user_id = $user->id;
                    $copy->is_template = false;
                    $copy->save();
                    $cloned[$id] = $copy;
                }
            }
            $items = array_map(fn ($line) => isset($line['design_id'], $cloned[$line['design_id']]) ? ['design_id' => $cloned[$line['design_id']]->id] + $line : $line, $items);
            foreach ($cloned as $orig => $copy) {
                $designs->forget($orig);
                $designs->put($copy->id, $copy);
            }

            $orderItems = [];
            $subtotal = '0';

            foreach ($items as $index => $line) {
                // ── Tayyor mahsulot (admin kiritgan, dizaynsiz) ──────────────────
                if (! empty($line['ready_product_id'])) {
                    $ready = $readyProducts->get($line['ready_product_id'])
                        ?? throw ValidationException::withMessages(["items.$index.ready_product_id" => 'Mahsulot topilmadi.']);

                    if ($ready->quantity > 0 && $ready->quantity < $line['quantity']) {
                        throw ValidationException::withMessages([
                            "items.$index.quantity" => "Omborda yetarli emas. Mavjud: {$ready->quantity}",
                        ]);
                    }

                    $lineTotal = bcmul((string) $ready->price, (string) $line['quantity'], 2);
                    $subtotal = bcadd($subtotal, $lineTotal, 2);

                    $orderItems[] = [
                        'ready_product_id' => $ready->id,
                        'product_name' => $ready->translated('name'),
                        'color_name' => $ready->color_name ?: '—',
                        'color_hex' => $ready->color_hex ?: '#FFFFFF',
                        'size_name' => $line['size'] ?? '—',
                        'quantity' => $line['quantity'],
                        'unit_price' => $ready->price,
                        'print_price' => '0',
                        'total_price' => $lineTotal,
                    ];

                    if ($ready->quantity > 0) {
                        $ready->decrement('quantity', $line['quantity']);
                    }

                    continue;
                }

                $variant = $variants->get($line['product_variant_id'])
                    ?? throw ValidationException::withMessages(["items.$index.product_variant_id" => 'Variant topilmadi.']);

                if ($variant->available < $line['quantity']) {
                    throw ValidationException::withMessages([
                        "items.$index.quantity" => "Omborda yetarli emas. Mavjud: {$variant->available}",
                    ]);
                }

                $design = null;
                if (! empty($line['design_id'])) {
                    $design = $designs->get($line['design_id'])
                        ?? throw ValidationException::withMessages(["items.$index.design_id" => 'Dizayn topilmadi.']);

                    if ($design->product_color_id !== $variant->product_color_id) {
                        throw ValidationException::withMessages(["items.$index.design_id" => 'Dizayn boshqa mahsulot/rangga tegishli.']);
                    }
                }

                $unitPrice = $variant->productColor->price ?? $variant->product->base_price;
                $printPrice = $design ? $variant->product->print_price : '0';
                $lineTotal = bcmul(bcadd($unitPrice, $printPrice, 2), (string) $line['quantity'], 2);
                $subtotal = bcadd($subtotal, $lineTotal, 2);

                $orderItems[] = [
                    'design_id' => $design?->id,
                    'product_variant_id' => $variant->id,
                    'product_name' => $variant->product->translated('name'),
                    'color_name' => $variant->productColor->color->translated('name'),
                    'color_hex' => $variant->productColor->color->hex,
                    'size_name' => $variant->size->name,
                    'quantity' => $line['quantity'],
                    'unit_price' => $unitPrice,
                    'print_price' => $printPrice,
                    'total_price' => $lineTotal,
                    'print_file_id' => $design?->print_file_id,
                ];

                $variant->increment('reserved', $line['quantity']);
            }

            $deliveryFee = (string) ($delivery['delivery_fee'] ?? '0');
            $total = bcadd($subtotal, $deliveryFee, 2);

            $order = Order::query()->create([
                'number' => Order::nextNumber(),
                'user_id' => $user->id,
                'status' => OrderStatus::New,
                'payment_method' => $paymentMethod,
                'subtotal' => $subtotal,
                'delivery_fee' => $deliveryFee,
                'total' => $total,
                'recipient_name' => $delivery['recipient_name'],
                'recipient_phone' => $delivery['recipient_phone'],
                'delivery_address' => $delivery['delivery_address'],
                'note' => $delivery['note'] ?? null,
            ]);

            $order->items()->createMany($orderItems);
            $order->histories()->create(['to_status' => OrderStatus::New, 'changed_by' => $user->id]);

            StockMovement::query()->insert(collect($orderItems)->filter(fn ($i) => ! empty($i['product_variant_id']))->map(fn ($i) => [
                'product_variant_id' => $i['product_variant_id'],
                'type' => StockMovementType::Reserve->value,
                'quantity' => -$i['quantity'],
                'reference_type' => $order->getMorphClass(),
                'reference_id' => $order->id,
                'created_by' => $user->id,
                'created_at' => now(),
            ])->all());

            if ($designs->isNotEmpty()) {
                Design::query()->whereIn('id', $designs->keys())->update(['status' => DesignStatus::Ready]);
            }

            return $order;
        });
    }
}
