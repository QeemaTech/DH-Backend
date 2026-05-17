<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CountryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $locale = app()->getLocale();

        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name[$locale] ?? $this->name['en'] ?? $this->code,
            'name_translations' => $this->name,
            'dial_code' => $this->dial_code,
            'flag' => $this->flag,
        ];
    }
}
