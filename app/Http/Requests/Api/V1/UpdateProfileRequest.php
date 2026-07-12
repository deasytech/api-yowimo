<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'username' => [
                'sometimes',
                'string',
                'min:3',
                'max:32',
                'regex:/^[a-zA-Z0-9_.]+$/',
                Rule::unique('users', 'username')->ignore($this->user()->id),
            ],
            'avatar_url' => ['sometimes', 'nullable', 'url', 'max:2048'],
            'first_name' => ['sometimes', 'nullable', 'string', 'max:100'],
            'last_name' => ['sometimes', 'nullable', 'string', 'max:100'],
            'display_name' => ['sometimes', 'nullable', 'string', 'max:100'],
            'bio' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'date_of_birth' => ['sometimes', 'nullable', 'date', 'before:today'],
            'country_code' => ['sometimes', 'nullable', 'string', 'size:2'],
            'interests' => ['sometimes', 'nullable', 'array'],
            'interests.*' => ['string', 'max:50'],
            'privacy_settings' => ['sometimes', 'nullable', 'array'],
        ];
    }
}
