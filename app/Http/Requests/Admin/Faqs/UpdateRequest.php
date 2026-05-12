<?php

namespace App\Http\Requests\Admin\Faqs;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'question' => ['required', 'array'],
            'question.*' => ['required', 'string', 'max:500'],
            'answer' => ['required', 'array'],
            'answer.*' => ['required', 'string', 'max:5000'],
            'order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'question.required' => __('The question is required.'),
            'question.*.required' => __('Each language translation for question is required.'),
            'question.*.max' => __('Each question translation must not exceed 500 characters.'),
            'answer.required' => __('The answer is required.'),
            'answer.*.required' => __('Each language translation for answer is required.'),
            'answer.*.max' => __('Each answer translation must not exceed 5000 characters.'),
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
        ]);
    }
}
