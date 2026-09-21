<?php

namespace App\Models;

use App\Enums\BatchStatus;
use App\Support\SequenceNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** @property-read int|null $items_sum_quantity  withSum('items', 'quantity') orqali */
class InventoryBatch extends Model
{
    protected $fillable = ['number', 'supplier_country', 'supplier_name', 'arrived_at', 'status', 'note', 'created_by', 'received_at'];

    protected $casts = ['status' => BatchStatus::class, 'arrived_at' => 'date', 'received_at' => 'datetime'];

    /** @return HasMany<InventoryBatchItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(InventoryBatchItem::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function nextNumber(): string
    {
        return SequenceNumber::next('inventory_batches', 'BATCH', 4);
    }
}
