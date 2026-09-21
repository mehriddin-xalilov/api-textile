<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryBatchItem extends Model
{
    protected $fillable = ['inventory_batch_id', 'product_variant_id', 'quantity', 'unit_cost'];

    protected $casts = ['quantity' => 'integer', 'unit_cost' => 'decimal:2'];

    /** @return BelongsTo<InventoryBatch, $this> */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(InventoryBatch::class, 'inventory_batch_id');
    }

    /** @return BelongsTo<ProductVariant, $this> */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
