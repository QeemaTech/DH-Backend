<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariantResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $locale = app()->getLocale();

        return [
            'id' => $this->id,
            'name' => $this->getTranslation('name', $locale, false) ?? $this->getTranslation('name', 'en', false),
            'slug' => $this->slug,
            'sku' => $this->sku,
            'stock' => $this->stock ?? 0,
            'price' => $this->price,
            'is_active' => $this->is_active ?? true,
            'images' => ImageResource::collection($this->whenLoaded('images')),
            'values' => ProductVariantValueResource::collection($this->whenLoaded('values')),
        ];
    }
}
