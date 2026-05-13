<?php

namespace App\Http\Requests\Admin\Countries;

use App\Enums\VerificationChannel;
use App\Models\Country;
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
        /** @var Country|null $country */
        $country = $this->route('country');

        return [
            'code' => ['required', 'string', 'size:2', Rule::unique('countries', 'code')->ignore($country?->id)],
            'name_en' => ['required', 'string', 'max:255'],
            'name_ar' => ['required', 'string', 'max:255'],
            'dial_code' => ['nullable', 'string', 'max:16'],
            'verification_channel' => ['required', Rule::enum(VerificationChannel::class)],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ];
    }
}

