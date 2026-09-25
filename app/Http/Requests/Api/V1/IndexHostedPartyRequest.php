<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\PartyStatus;
use App\Http\Requests\Api\V1\Concerns\HasCursorPagination;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexHostedPartyRequest extends FormRequest
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
            'status' => ['sometimes', 'nullable', Rule::enum(PartyStatus::class)],
            ...$this->cursorPaginationRules(),
        ];
    }
}
