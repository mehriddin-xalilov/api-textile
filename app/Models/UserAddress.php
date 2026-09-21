<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Saqlangan manzil. `full` — buyurtmaga yoziladigan bitta qator. */
class UserAddress extends Model
{
    protected $fillable = [
        'user_id', 'label', 'recipient_name', 'recipient_phone',
        'region', 'city', 'street', 'apartment', 'landmark', 'note', 'is_default',
    ];

    protected $casts = ['is_default' => 'boolean'];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Buyurtmada ishlatiladigan to'liq manzil qatori. */
    public function getFullAttribute(): string
    {
        $parts = array_filter([
            $this->region,
            $this->city,
            $this->street,
            $this->apartment ? "kv. {$this->apartment}" : null,
            $this->landmark ? "({$this->landmark})" : null,
        ]);

        return implode(', ', $parts);
    }
}
