<?php

namespace App\Http\Requests\Api\V1;

use App\Http\Requests\Api\V1\Concerns\HasCursorPagination;
use Illuminate\Foundation\Http\FormRequest;

class IndexTokenBundleRequest extends FormRequest
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
            ...$this->cursorPaginationRules(),
        ];
    }
}
