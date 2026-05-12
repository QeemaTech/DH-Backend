<?php

namespace App\Http\Requests\Api\Auth;

use App\Enums\VerificationChannel;
use App\Models\Country;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'country_id' => ['required', 'exists:countries,id'],
            'email' => [
                'nullable',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class),
            ],
            'phone' => [
                'required',
                'string',
                'max:255',
                Rule::unique('users', 'phone')->where('country_id', $this->input('country_id')),
            ],
            'password' => ['required', 'string', Password::defaults(), 'confirmed'],
            'referred_by_code' => ['nullable', 'string', 'exists:users,referral_code'],
            'gender' => ['nullable', 'string', Rule::in(['male', 'female', 'other'])],
            'birth_date' => ['nullable', 'date'],
            'national_number' => ['nullable', 'string', 'max:255'],
            'national_id_expire_date' => ['nullable', 'date'],
            'home_address' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $countryId = $this->input('country_id');
            if (! $countryId) {
                return;
            }

            $country = Country::query()->find($countryId);
            if ($country && $country->verification_channel === VerificationChannel::Email && empty($this->input('email'))) {
                $validator->errors()->add('email', __('The email field is required for the selected country.'));
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => __('The name field is required.'),
            'name.string' => __('The name must be a string.'),
            'name.max' => __('The name must not exceed 255 characters.'),
            'country_id.required' => __('Please select your country.'),
            'country_id.exists' => __('The selected country is invalid.'),
            'email.email' => __('The email must be a valid email address.'),
            'email.lowercase' => __('The email must be lowercase.'),
            'email.unique' => __('The email has already been taken.'),
            'phone.required' => __('The phone field is required.'),
            'phone.unique' => __('The phone has already been taken.'),
            'password.required' => __('The password field is required.'),
            'password.confirmed' => __('The password confirmation does not match.'),
            'referred_by_code.exists' => __('The referred by code is invalid.'),
        ];
    }
}
