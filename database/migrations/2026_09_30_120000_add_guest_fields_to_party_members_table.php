<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('party_members', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
            $table->string('guest_name')->nullable()->after('user_id');
            $table->string('guest_emoji')->nullable()->after('guest_name');
            $table->string('join_mode')->nullable()->after('guest_emoji');
        });
    }

    /**
     * Reverse the migrations. A guest (user_id null) row can't survive
     * restoring the NOT NULL constraint, so guest rows are removed first —
     * each affected party's players_count is decremented to match, the same
     * bookkeeping join()/leave() do for a real membership change.
     */
    public function down(): void
    {
        $guestCounts = DB::table('party_members')
            ->whereNull('user_id')
            ->selectRaw('party_id, count(*) as guest_count')
            ->groupBy('party_id')
            ->pluck('guest_count', 'party_id');

        DB::table('party_members')->whereNull('user_id')->delete();

        foreach ($guestCounts as $partyId => $guestCount) {
            $party = DB::table('parties')->where('id', $partyId)->first(['players_count']);

            if ($party) {
                DB::table('parties')->where('id', $partyId)->update([
                    'players_count' => max(0, $party->players_count - $guestCount),
                ]);
            }
        }

        Schema::table('party_members', function (Blueprint $table) {
            $table->dropColumn(['guest_name', 'guest_emoji', 'join_mode']);
            $table->foreignId('user_id')->nullable(false)->change();
        });
    }
};
