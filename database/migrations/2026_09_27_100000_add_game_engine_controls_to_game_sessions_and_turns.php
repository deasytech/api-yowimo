<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Turn timer length used before it became host-configurable.
     */
    private const LEGACY_TURN_SECONDS = 30;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('game_sessions', function (Blueprint $table) {
            $table->unsignedSmallInteger('turn_seconds')->default(self::LEGACY_TURN_SECONDS)->after('current_turn_index');
            $table->timestamp('paused_at')->nullable()->after('turn_seconds');
            $table->unsignedSmallInteger('paused_turn_remaining_seconds')->nullable()->after('paused_at');
            $table->timestamp('voting_ends_at')->nullable()->after('paused_turn_remaining_seconds');
        });

        Schema::table('turns', function (Blueprint $table) {
            $table->timestamp('expires_at')->nullable()->after('started_at');
            $table->boolean('is_skipped')->default(false)->after('is_afk');
        });

        // Turns dealt before this migration expired a fixed 30s after they started.
        DB::table('turns')->whereNull('expires_at')->orderBy('id')->chunkById(500, function ($turns) {
            foreach ($turns as $turn) {
                DB::table('turns')->where('id', $turn->id)->update([
                    'expires_at' => date('Y-m-d H:i:s', strtotime($turn->started_at) + self::LEGACY_TURN_SECONDS),
                ]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('turns', function (Blueprint $table) {
            $table->dropColumn(['expires_at', 'is_skipped']);
        });

        Schema::table('game_sessions', function (Blueprint $table) {
            $table->dropColumn(['turn_seconds', 'paused_at', 'paused_turn_remaining_seconds', 'voting_ends_at']);
        });
    }
};
