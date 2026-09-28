<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFriendshipRequest extends FormRequest
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
            'receiver_id' => [
                'required',
                'integer',
                // whereNull('deleted_at') so a soft-deleted receiver fails
                // validation (422) here rather than reaching the controller's
                // findOrFail(), which is soft-delete-aware and would 404.
                Rule::exists('users', 'id')->whereNull('deleted_at'),
                Rule::notIn([$this->user()->id]),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'receiver_id.not_in' => 'You cannot send a friend request to yourself.',
        ];
    }
}
