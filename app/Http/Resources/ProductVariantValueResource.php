<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariantValueResource extends JsonResource
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
            'value' => $this->getTranslation('value', $locale, false) ?? $this->getTranslation('value', 'en', false),
            'variant_option_id' => $this->variant_option_id,
            'variant_option' => new VariantOptionResource($this->whenLoaded('variantOption')),
        ];
    }
}
