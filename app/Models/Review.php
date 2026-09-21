<?php

namespace App\Models;

use App\Enums\ReviewStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Mahsulot/tayyor dizayn haqida mijoz izohi (moderatsiya bilan). */
class Review extends Model
{
    protected $fillable = ['user_id', 'product_id', 'ready_product_id', 'design_id', 'order_id', 'rating', 'comment', 'reply', 'status'];

    protected $casts = ['status' => ReviewStatus::class, 'rating' => 'integer'];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return BelongsTo<ReadyProduct, $this> */
    public function readyProduct(): BelongsTo
    {
        return $this->belongsTo(ReadyProduct::class);
    }

    /** @return BelongsTo<Design, $this> */
    public function design(): BelongsTo
    {
        return $this->belongsTo(Design::class);
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
