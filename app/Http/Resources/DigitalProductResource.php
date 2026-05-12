<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DigitalProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'company_name' => $this->company_name,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'how_to_use' => $this->how_to_use,
            'image' => $this->image,
            'cost_after_vat' => (float) $this->cost_after_vat,
            'price' => (float) $this->price,
            'currency' => $this->currency,
            'is_active' => (bool) $this->is_active,
            'is_available' => (bool) $this->is_available,
            'visits' => (int) $this->visits,
            'merchant' => $this->whenLoaded('merchant', function () {
                return [
                    'id' => $this->merchant?->id,
                    'merchant_id' => $this->merchant?->merchant_id,
                    'company_name' => $this->merchant?->company_name,
                    'name' => $this->merchant?->name,
                ];
            }),
            'category' => $this->whenLoaded('category', function () {
                return [
                    'id' => $this->category?->id,
                    'name' => $this->category?->name,
                    'slug' => $this->category?->slug,
                ];
            }),
            'sub_category' => $this->whenLoaded('subCategory', function () {
                return [
                    'id' => $this->subCategory?->id,
                    'name' => $this->subCategory?->name,
                    'slug' => $this->subCategory?->slug,
                ];
            }),
        ];
    }
}
