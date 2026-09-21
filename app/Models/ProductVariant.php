<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductVariant extends Model
{
    protected $fillable = ['product_id', 'product_color_id', 'size_id', 'sku', 'quantity', 'reserved'];

    protected $casts = ['quantity' => 'integer', 'reserved' => 'integer'];

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return BelongsTo<ProductColor, $this> */
    public function productColor(): BelongsTo
    {
        return $this->belongsTo(ProductColor::class);
    }

    /** @return BelongsTo<Size, $this> */
    public function size(): BelongsTo
    {
        return $this->belongsTo(Size::class);
    }

    /** @return HasMany<StockMovement, $this> */
    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function getAvailableAttribute(): int
    {
        return $this->quantity - $this->reserved;
    }

    public function scopeInStock(Builder $query): Builder
    {
        return $query->whereRaw('quantity - reserved > 0');
    }
}
