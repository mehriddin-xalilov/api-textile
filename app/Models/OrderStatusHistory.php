<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderStatusHistory extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['order_id', 'from_status', 'to_status', 'comment', 'changed_by'];

    protected $casts = ['from_status' => OrderStatus::class, 'to_status' => OrderStatus::class];

    /** @return BelongsTo<User, $this> */
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
