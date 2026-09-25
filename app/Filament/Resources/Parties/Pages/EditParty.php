<?php

namespace App\Filament\Resources\Parties\Pages;

use App\Filament\Resources\Parties\PartyResource;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditParty extends EditRecord
{
    protected static string $resource = PartyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Defense in depth beyond the fields' own disabled() state (which a
        // manipulated Livewire request can bypass client-side — see
        // Filament's own CanBeDisabled trait): once a game session exists,
        // silently keep the party's original game_type_id/pack_id
        // regardless of what was submitted, matching the API's
        // PartyGameAlreadyStartedException enforcement.
        if ($this->record->gameSessions()->exists()) {
            $data['game_type_id'] = $this->record->game_type_id;
            $data['pack_id'] = $this->record->pack_id;
        }

        // See CreateParty::mutateFormDataBeforeCreate() for why this can't
        // rely on the field's visible() state alone.
        if (! ($data['is_sponsored'] ?? false)) {
            $data['sponsor_name'] = null;
        }

        return $data;
    }
}
