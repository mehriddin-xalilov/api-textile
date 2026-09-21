<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id', 'design_id', 'product_variant_id', 'ready_product_id',
        'product_name', 'color_name', 'color_hex', 'size_name',
        'quantity', 'unit_price', 'print_price', 'total_price', 'print_file_id',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2', 'print_price' => 'decimal:2', 'total_price' => 'decimal:2',
    ];

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** @return BelongsTo<Design, $this> */
    public function design(): BelongsTo
    {
        return $this->belongsTo(Design::class);
    }

    /** @return BelongsTo<ReadyProduct, $this> */
    public function readyProduct(): BelongsTo
    {
        return $this->belongsTo(ReadyProduct::class);
    }

    /** @return BelongsTo<ProductVariant, $this> */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /** @return BelongsTo<File, $this> */
    public function printFile(): BelongsTo
    {
        return $this->belongsTo(File::class, 'print_file_id');
    }
}
