<?php

namespace App\Models;

use App\Enums\Status;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductColor extends Model
{
    protected $fillable = ['product_id', 'color_id', 'price', 'front_image_id', 'back_image_id', 'left_image_id', 'right_image_id', 'status'];

    protected $casts = ['status' => Status::class, 'price' => 'decimal:2'];

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return BelongsTo<Color, $this> */
    public function color(): BelongsTo
    {
        return $this->belongsTo(Color::class);
    }

    /** @return BelongsTo<File, $this> */
    public function frontImage(): BelongsTo
    {
        return $this->belongsTo(File::class, 'front_image_id');
    }

    /** @return BelongsTo<File, $this> */
    public function backImage(): BelongsTo
    {
        return $this->belongsTo(File::class, 'back_image_id');
    }

    /** @return BelongsTo<File, $this> */
    public function leftImage(): BelongsTo
    {
        return $this->belongsTo(File::class, 'left_image_id');
    }

    /** @return BelongsTo<File, $this> */
    public function rightImage(): BelongsTo
    {
        return $this->belongsTo(File::class, 'right_image_id');
    }

    /** @return HasMany<ProductVariant, $this> */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    /** Rang uchun narx: o'ziniki bo'lmasa mahsulot bazaviy narxi. */
    public function effectivePrice(): string
    {
        return $this->price ?? $this->product->base_price;
    }
}
