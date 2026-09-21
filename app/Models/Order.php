<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Support\SequenceNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'number', 'user_id', 'status', 'payment_status', 'payment_method',
        'subtotal', 'discount', 'delivery_fee', 'total', 'currency',
        'recipient_name', 'recipient_phone', 'delivery_address', 'note',
        'confirmed_at', 'shipped_at', 'delivered_at', 'cancelled_at',
    ];

    protected $casts = [
        'status' => OrderStatus::class,
        'payment_status' => PaymentStatus::class,
        'payment_method' => PaymentMethod::class,
        'subtotal' => 'decimal:2', 'discount' => 'decimal:2',
        'delivery_fee' => 'decimal:2', 'total' => 'decimal:2',
        'confirmed_at' => 'datetime', 'shipped_at' => 'datetime',
        'delivered_at' => 'datetime', 'cancelled_at' => 'datetime',
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<OrderItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /** @return HasMany<OrderStatusHistory, $this> */
    public function histories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->latest('id');
    }

    /** @return HasMany<PaymentTransaction, $this> */
    public function transactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class)->latest('id');
    }

    public static function nextNumber(): string
    {
        return SequenceNumber::next('orders', 'ORD', 6);
    }
}
