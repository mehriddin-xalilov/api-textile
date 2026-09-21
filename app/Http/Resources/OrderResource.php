<?php

namespace App\Http\Resources;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Order */
class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'user_id' => $this->user_id,
            'status' => $this->status,
            'status_label' => $this->status->label(),
            'allowed_transitions' => $this->status->transitions(),
            'payment_status' => $this->payment_status,
            'payment_method' => $this->payment_method,
            'subtotal' => $this->subtotal,
            'discount' => $this->discount,
            'delivery_fee' => $this->delivery_fee,
            'total' => $this->total,
            'currency' => $this->currency,
            'recipient_name' => $this->recipient_name,
            'recipient_phone' => $this->recipient_phone,
            'delivery_address' => $this->delivery_address,
            'note' => $this->note,
            'items_count' => $this->whenCounted('items'),
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'histories' => OrderStatusHistoryResource::collection($this->whenLoaded('histories')),
            'transactions' => $this->whenLoaded('transactions', fn () => $this->transactions->map(fn ($t) => ['id' => $t->id, 'provider' => $t->provider, 'provider_transaction_id' => $t->provider_transaction_id, 'amount' => $t->amount, 'state' => $t->state, 'performed_at' => $t->performed_at, 'created_at' => $t->created_at])),
            'user' => new UserResource($this->whenLoaded('user')),
            'confirmed_at' => $this->confirmed_at,
            'shipped_at' => $this->shipped_at,
            'delivered_at' => $this->delivered_at,
            'cancelled_at' => $this->cancelled_at,
            'created_at' => $this->created_at,
        ];
    }
}
