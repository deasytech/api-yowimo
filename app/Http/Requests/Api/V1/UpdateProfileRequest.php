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
        ];
    }
}
