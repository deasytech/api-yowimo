<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePartyRequest extends FormRequest
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
            'game_type_id' => ['sometimes', 'nullable', 'integer', 'exists:game_types,id'],
            'pack_id' => ['sometimes', 'nullable', 'integer', 'exists:packs,id'],
        ];
    }
}
