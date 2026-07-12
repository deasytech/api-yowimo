<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\PartyMode;
use App\Enums\PartyVisibility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePartyRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:100'],
            'description' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'game_type_id' => ['sometimes', 'nullable', 'integer', 'exists:game_types,id'],
            'pack_id' => ['sometimes', 'nullable', 'integer', 'exists:packs,id'],
            'mode' => ['required', Rule::enum(PartyMode::class)],
            'visibility' => ['required', Rule::enum(PartyVisibility::class)],
            'max_players' => ['sometimes', 'integer', 'min:2', 'max:200'],
            'starts_at' => ['sometimes', 'nullable', 'date', 'after_or_equal:now'],
            'save_as_draft' => ['sometimes', 'boolean'],
            'location' => ['required_if:mode,hybrid,in_person', 'nullable', 'array'],
            'location.venue_name' => ['sometimes', 'nullable', 'string', 'max:150'],
            'location.address' => ['sometimes', 'nullable', 'string', 'max:255'],
            'location.latitude' => ['sometimes', 'nullable', 'numeric', 'between:-90,90'],
            'location.longitude' => ['sometimes', 'nullable', 'numeric', 'between:-180,180'],
            'tags' => ['sometimes', 'nullable', 'array', 'max:5'],
            'tags.*' => ['string', 'max:20'],
        ];
    }
}
