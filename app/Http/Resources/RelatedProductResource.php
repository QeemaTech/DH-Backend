<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RelatedProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $locale = app()->getLocale();

        return [
            'id' => $this->id,
            'name' => $this->getTranslation('name', $locale, false) ?? $this->getTranslation('name', 'en', false),
            'slug' => $this->slug,
            'thumb_image' => $this->main_image,
            'description' => $this->getTranslation('description', $locale, false) ?? $this->getTranslation('description', 'en', false),
            'discount' => $this->discount,
            'discount_type' => $this->discount_type,
            'is_active' => $this->is_active,
            'is_featured' => $this->is_featured,
            'is_approved' => $this->is_approved,
            'is_bookable' => $this->is_bookable,
            'is_new' => $this->is_new,
            'price' => $this->manager()->price(),
            'stock' => $this->manager()->stock(),
            'vendor' => new VendorResource($this->whenLoaded('vendor')),
            'categories' => CategoryResource::collection($this->whenLoaded('categories')),
            'images' => ImageResource::collection($this->whenLoaded('images')),
        ];
    }
}
