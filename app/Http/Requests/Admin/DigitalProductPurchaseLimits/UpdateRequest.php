<?php

namespace App\Http\Requests\Admin\DigitalProductPurchaseLimits;

use App\Models\DigitalProductPurchaseLimit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'verification_level' => ['required', Rule::in([
                DigitalProductPurchaseLimit::VERIFICATION_CONTACT,
                DigitalProductPurchaseLimit::VERIFICATION_FULLY,
            ])],
            'period_type' => ['required', Rule::in([
                DigitalProductPurchaseLimit::PERIOD_DAILY,
                DigitalProductPurchaseLimit::PERIOD_WEEKLY,
                DigitalProductPurchaseLimit::PERIOD_MONTHLY,
            ])],
            'limit_amount' => ['required', 'numeric', 'gt:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
