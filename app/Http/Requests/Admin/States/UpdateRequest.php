<?php

namespace App\Http\Requests\Admin\States;

use App\Models\State;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') ?? false;
    }

    public function rules(): array
    {
        /** @var State|null $state */
        $state = $this->route('state');

        return [
            'country_id' => ['required', 'integer', 'exists:countries,id'],
            'name_en' => ['required', 'string', 'max:255'],
            'name_ar' => ['required', 'string', 'max:255'],
            'code' => [
                'nullable',
                'string',
                'max:32',
                Rule::unique('states', 'code')
                    ->ignore($state?->id)
                    ->where(fn ($q) => $q->where('country_id', (int) $this->input('country_id'))),
            ],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ];
    }
}

