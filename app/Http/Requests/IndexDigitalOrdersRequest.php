<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IndexDigitalOrdersRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() != null;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
            'payment_status' => ['sometimes', 'nullable', 'string', 'in:pending,paid,failed,refunded'],
            'status' => ['sometimes', 'nullable', 'string', 'in:pending,processing,shipped,delivered,cancelled,refunded'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'per_page.integer' => __('The items per page must be a whole number.'),
            'per_page.min' => __('The items per page must be at least 1.'),
            'per_page.max' => __('The items per page may not be greater than 50.'),
            'payment_status.in' => __('The selected payment status is invalid.'),
            'status.in' => __('The selected order status is invalid.'),
        ];
    }

    public function validationData(): array
    {
        return $this->query();
    }
}
