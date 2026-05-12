<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DigitalOrderItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'digital_product_id' => $this->digital_product_id,
            'price' => (float) $this->price,
            'quantity' => (int) $this->quantity,
            'total' => (float) $this->total,
            'notes' => $this->notes,
            'provider_reference' => $this->provider_reference,
            'provider_response' => $this->provider_response,
            'delivered_data' => $this->delivered_data,
            'delivered_at' => $this->delivered_at?->toIso8601String(),
            'digital_product' => new DigitalProductResource($this->whenLoaded('digitalProduct')),
        ];
    }
}
