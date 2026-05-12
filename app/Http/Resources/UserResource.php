<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'email' => $this->email,
            'phone' => $this->phone,
            'country_id' => $this->country_id,
            'country' => $this->whenLoaded('country', fn () => new CountryResource($this->country)),
            'image' => $this->image,
            'gender' => $this->gender,
            'birth_date' => $this->birth_date?->format('Y-m-d'),
            'national_number' => $this->national_number,
            'national_cart_front_image' => $this->national_cart_front_image,
            'national_cart_back_image' => $this->national_cart_back_image,
            'national_id_expire_date' => $this->national_id_expire_date?->format('Y-m-d'),
            'home_address' => $this->home_address,
            'email_verified_at' => $this->email_verified_at?->toIso8601String(),
            'phone_verified_at' => $this->phone_verified_at?->toIso8601String(),
            'is_active' => $this->is_active,
            'is_verified' => $this->is_verified,
            'roles' => $this->when(
                $this->relationLoaded('roles'),
                fn () => $this->roles->pluck('name')
            ),
            'permissions' => $this->when(
                $this->relationLoaded('permissions'),
                fn () => $this->permissions->pluck('name')
            ),
        ];
    }
}
