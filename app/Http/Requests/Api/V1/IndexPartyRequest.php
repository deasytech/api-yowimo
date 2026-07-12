<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\PartyMode;
use App\Http\Requests\Api\V1\Concerns\HasCursorPagination;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexPartyRequest extends FormRequest
{
    use HasCursorPagination;

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
            'mode' => ['sometimes', 'nullable', Rule::enum(PartyMode::class)],
            'game_type_id' => ['sometimes', 'nullable', 'integer', 'exists:game_types,id'],
            'search' => ['sometimes', 'nullable', 'string', 'max:100'],
            ...$this->cursorPaginationRules(),
        ];
    }
}
