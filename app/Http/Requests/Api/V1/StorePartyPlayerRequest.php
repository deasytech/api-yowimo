<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\JoinMode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePartyPlayerRequest extends FormRequest
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
            'guest_name' => ['required', 'string', 'max:60'],
            'guest_emoji' => ['sometimes', 'nullable', 'string', 'max:8'],
            'join_mode' => ['required', Rule::enum(JoinMode::class)],
        ];
    }
}
