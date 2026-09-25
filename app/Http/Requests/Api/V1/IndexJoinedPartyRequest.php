<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\PartyMemberStatus;
use App\Http\Requests\Api\V1\Concerns\HasCursorPagination;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexJoinedPartyRequest extends FormRequest
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
            'membership_status' => ['sometimes', 'nullable', Rule::enum(PartyMemberStatus::class)],
            ...$this->cursorPaginationRules(),
        ];
    }
}
