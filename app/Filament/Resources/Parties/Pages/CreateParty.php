<?php

namespace App\Filament\Resources\Parties\Pages;

use App\Enums\PartyMemberStatus;
use App\Events\PartyCreated;
use App\Filament\Resources\Parties\PartyResource;
use App\Models\PartyMember;
use App\Services\Parties\RoomCodeGenerator;
use Filament\Resources\Pages\CreateRecord;

class CreateParty extends CreateRecord
{
    protected static string $resource = PartyResource::class;

    /**
     * Wraps create() + afterCreate() (where the host's PartyMember row is
     * made) in one transaction, so a failure creating that row rolls back
     * the party record too instead of leaving an orphaned party with no
     * members.
     */
    protected ?bool $hasDatabaseTransactions = true;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['room_code'] = app(RoomCodeGenerator::class)->generate();
        $data['players_count'] = 1;

        // The sponsor_name field is only visible in the UI when is_sponsored
        // is checked; visible() alone doesn't stop a stale value from being
        // dehydrated, so it's cleared explicitly here rather than trusting
        // the submitted payload.
        if (! ($data['is_sponsored'] ?? false)) {
            $data['sponsor_name'] = null;
        }

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
            'status' => PartyMemberStatus::Active,
            'joined_at' => now(),
        ]);

        PartyCreated::dispatch($this->record->id, $this->record->host_id);
    }
}
