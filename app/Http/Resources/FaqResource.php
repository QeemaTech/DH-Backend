<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FaqResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $locale = app()->getLocale();
        $questionTranslations = method_exists($this->resource, 'getTranslations')
            ? $this->getTranslations('question')
            : (array) ($this->question ?? []);
        $answerTranslations = method_exists($this->resource, 'getTranslations')
            ? $this->getTranslations('answer')
            : (array) ($this->answer ?? []);

        return [
            'id' => $this->id,
            'question' => $questionTranslations[$locale] ?? $questionTranslations['en'] ?? null,
            'question_translations' => $questionTranslations,
            'answer' => $answerTranslations[$locale] ?? $answerTranslations['en'] ?? null,
            'answer_translations' => $answerTranslations,
            'order' => $this->order,
        ];
    }
}
