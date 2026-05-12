<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VendorResource extends JsonResource
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
            'name' => $this->name,
            'image' => $this->image,
            'phone' => $this->phone,
            'address' => $this->address,
            'rating' => [
                'average' => (float) ($this->ratings()->where('is_visible', true)->avg('rating') ?? 0),
                'count' => (int) $this->ratings()->where('is_visible', true)->count(),
            ],
            'products' => ProductResource::collection($this->whenLoaded('products')),
            'branches' => BranchResource::collection($this->whenLoaded('branches')),
        ];
    }
}
