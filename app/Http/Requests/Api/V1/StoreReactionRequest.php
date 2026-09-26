<?php

namespace App\Http\Requests\Api\V1;

use App\Services\Game\ReactionService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReactionRequest extends FormRequest
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
            'emoji' => ['required', 'string', Rule::in(ReactionService::EMOJIS)],
        ];
    }
}
