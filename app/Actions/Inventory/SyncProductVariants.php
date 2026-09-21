<?php

namespace App\Actions\Inventory;

use App\Models\ProductColor;
use App\Models\ProductVariant;
use App\Models\Size;
use Illuminate\Support\Str;

/**
 * Mahsulot rangi uchun barcha faol razmerlarda variant (SKU) yaratadi.
 * Mavjudlari saqlanadi — faqat yetishmaganlari qo'shiladi.
 */
final class SyncProductVariants
{
    public function handle(ProductColor $productColor, ?array $sizeIds = null): void
    {
        $productColor->loadMissing(['product', 'color']);

        $sizes = Size::query()
            ->when($sizeIds, fn ($q) => $q->whereIn('id', $sizeIds), fn ($q) => $q->where('status', 'active'))
            ->orderBy('sort')
            ->get();

        $existing = $productColor->variants()->pluck('size_id')->all();

        $rows = $sizes->reject(fn (Size $s) => in_array($s->id, $existing, true))
            ->map(fn (Size $size) => [
                'product_id' => $productColor->product_id,
                'product_color_id' => $productColor->id,
                'size_id' => $size->id,
                'sku' => $this->sku($productColor, $size),
                'quantity' => 0,
                'reserved' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ])->values()->all();

        if ($rows) {
            ProductVariant::query()->insert($rows);
        }
    }

    private function sku(ProductColor $pc, Size $size): string
    {
        $product = Str::upper(Str::substr(Str::slug($pc->product->name_en ?: $pc->product->name_uz, ''), 0, 6));
        $color = Str::upper(Str::substr(Str::slug($pc->color->name_en ?: $pc->color->name_uz, ''), 0, 4));

        return sprintf('%s-%s-%s-%d', $product, $color, $size->name, $pc->id);
    }
}
