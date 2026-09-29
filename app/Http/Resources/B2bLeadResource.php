<?php

namespace App\Http\Resources;

use App\Models\B2bLead;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin B2bLead */
class B2bLeadResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'token' => $this->token,
            'company' => $this->company,
            'email' => $this->email,
            'phone' => $this->phone,
            'segment' => $this->segment,
            'campaign' => $this->campaign,
            'sent_at' => $this->sent_at,
            'opened_at' => $this->opened_at,
            'first_click_at' => $this->first_click_at,
            'last_click_at' => $this->last_click_at,
            'clicks' => $this->clicks,
            'last_path' => $this->last_path,
            'note' => $this->note,
            'user' => new UserResource($this->whenLoaded('user')),
        ];
    }
}
