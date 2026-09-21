<?php

namespace App\Models;

use App\Enums\ProductSide;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrintArea extends Model
{
    protected $fillable = ['product_id', 'side', 'name', 'x', 'y', 'width', 'height', 'max_width_cm', 'max_height_cm'];

    protected $casts = [
        'side' => ProductSide::class,
        'x' => 'float', 'y' => 'float', 'width' => 'float', 'height' => 'float',
        'max_width_cm' => 'float', 'max_height_cm' => 'float',
    ];

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
