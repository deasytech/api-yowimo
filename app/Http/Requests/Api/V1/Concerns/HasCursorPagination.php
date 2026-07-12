<?php

namespace App\Http\Requests\Api\V1\Concerns;

trait HasCursorPagination
{
    /**
     * @return array<string, mixed>
     */
    protected function cursorPaginationRules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
            'cursor' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
