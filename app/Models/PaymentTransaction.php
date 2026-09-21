<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentTransaction extends Model
{
    public const STATE_CREATED = 'created';

    public const STATE_PERFORMED = 'performed';

    public const STATE_CANCELLED = 'cancelled';

    protected $fillable = ['order_id', 'provider', 'provider_transaction_id', 'amount', 'state', 'payload', 'performed_at', 'cancelled_at', 'cancel_reason'];

    protected $casts = ['payload' => 'array', 'amount' => 'decimal:2', 'performed_at' => 'datetime', 'cancelled_at' => 'datetime'];

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
