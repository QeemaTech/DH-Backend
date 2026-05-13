<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFraudReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone_number' => ['required', 'string', 'max:30'],
            'card_last4' => ['required', 'digits:4'],
            'card_type' => ['required', Rule::in(['visa', 'mastercard', 'american_express', 'discover'])],
            'fraud_description' => ['required', 'string', 'max:5000'],
        ];
    }
}
