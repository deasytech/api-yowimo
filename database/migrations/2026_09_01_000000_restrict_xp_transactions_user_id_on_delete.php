<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // xp_transactions has no separate "account" row the way wallets does
        // for wallet_transactions — user_id plays both roles at once. Mirrors
        // wallet_transactions.wallet_id's restrictOnDelete (see
        // 2026_07_12_165259_create_wallet_transactions_table.php): this is an
        // append-only ledger and the source of truth for XP, so it shouldn't
        // silently disappear if a user is ever hard-deleted.
        Schema::table('xp_transactions', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('xp_transactions', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
