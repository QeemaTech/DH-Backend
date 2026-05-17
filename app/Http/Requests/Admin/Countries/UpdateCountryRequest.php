<?php

namespace App\Http\Requests\Admin\Countries;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCountryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'verification_channels' => ['required', 'array', 'min:1'],
            'verification_channels.*' => ['required', Rule::in(['sms', 'whatsapp', 'email'])],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
