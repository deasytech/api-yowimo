<?php

namespace App\Http\Requests\Api\V1;

use App\Services\Game\GameSessionService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StartGameSessionRequest extends FormRequest
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
            'rounds' => ['nullable', 'integer', Rule::in(GameSessionService::ALLOWED_ROUNDS_COUNTS)],
        ];
    }
}
