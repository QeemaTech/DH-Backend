<?php

namespace App\Http\Requests\Api\Profile;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = $this->user();
        $countryId = $this->input('country_id', $user->country_id);

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'country_id' => ['sometimes', 'nullable', 'exists:countries,id'],
            'email' => [
                'sometimes',
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($user->id),
            ],
            'phone' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
                Rule::unique('users', 'phone')->ignore($user->id)->where('country_id', $countryId),
            ],
            'image' => ['sometimes', 'nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg,webp', 'max:5120'],
            'gender' => ['nullable', 'string', Rule::in(['male', 'female', 'other'])],
            'birth_date' => ['nullable', 'date'],
            'national_number' => ['nullable', 'string', 'max:255'],
            'national_id_expire_date' => ['nullable', 'date'],
            'home_address' => ['nullable', 'string', 'max:2000'],
            'national_cart_front_image' => ['sometimes', 'nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:5120'],
            'national_cart_back_image' => ['sometimes', 'nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:5120'],
        ];
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
            'email.required' => __('The email field is required.'),
            'email.email' => __('The email must be a valid email address.'),
            'email.unique' => __('The email has already been taken.'),
            'phone.unique' => __('The phone has already been taken.'),
            'image.image' => __('The image must be an image file.'),
            'image.mimes' => __('The image must be a file of type: jpeg, png, jpg, gif, svg, webp.'),
            'image.max' => __('The image must not be larger than 5MB.'),
        ];
    }
}
