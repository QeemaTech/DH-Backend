<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DigitalOrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'payment_status' => $this->payment_status,
            'status' => $this->status,
            'notes' => $this->notes,
            'total' => (float) $this->total,
            'discount' => (float) $this->discount,
            'shipping_cost' => (float) $this->shipping_cost,
            'total_cost' => (float) $this->total_cost,
            'user_name' => $this->user_name,
            'user_email' => $this->user_email,
            'user_phone' => $this->user_phone,
            'user_country' => $this->user_country,
            'items' => DigitalOrderItemResource::collection($this->whenLoaded('items')),
            'items_count' => $this->when(
                $this->relationLoaded('items'),
                fn () => $this->items->count(),
                fn () => $this->items()->count()
            ),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
