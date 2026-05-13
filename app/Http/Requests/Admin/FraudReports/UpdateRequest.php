<?php

namespace App\Http\Requests\Admin\FraudReports;

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
        return [
            'status' => ['required', Rule::in(['in_review', 'resolved', 'rejected'])],
            'assigned_to' => ['nullable', 'integer', Rule::exists('users', 'id')->where(fn ($q) => $q->where('role', 'admin'))],
        ];
    }
}
