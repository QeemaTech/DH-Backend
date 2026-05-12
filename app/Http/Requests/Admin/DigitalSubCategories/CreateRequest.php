<?php

namespace App\Http\Requests\Admin\DigitalSubCategories;

use Illuminate\Foundation\Http\FormRequest;

class CreateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'array'],
            'name.*' => ['required', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg,webp', 'max:3072'],
            'thumbnail' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg,webp', 'max:3072'],
            'visits' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'digital_category_id' => ['required', 'integer', 'exists:digital_categories,id'],
            'last_update_by' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }
}
