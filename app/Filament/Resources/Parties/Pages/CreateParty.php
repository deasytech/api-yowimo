<?php

namespace App\Filament\Resources\Parties\Pages;

use App\Events\PartyCreated;
use App\Filament\Resources\Parties\PartyResource;
use App\Models\PartyMember;
use App\Services\Parties\RoomCodeGenerator;
use Filament\Resources\Pages\CreateRecord;

class CreateParty extends CreateRecord
{
    protected static string $resource = PartyResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['room_code'] = app(RoomCodeGenerator::class)->generate();
        $data['players_count'] = 1;

        return $data;
    }

    /**
     * The host is always the party's first member — the API creates this row
     * at the same time as the party itself, so an admin-created party must
     * match that invariant too.
     */
    protected function afterCreate(): void
    {
        PartyMember::create([
            'party_id' => $this->record->id,
            'user_id' => $this->record->host_id,
            'joined_at' => now(),
        ]);

        PartyCreated::dispatch($this->record->id, $this->record->host_id);
    }
}
