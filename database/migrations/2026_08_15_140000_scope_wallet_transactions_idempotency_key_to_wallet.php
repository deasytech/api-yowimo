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
        // A global-unique idempotency_key let two different users' requests
        // collide on the same key: the loser's insert would fail the unique
        // constraint and applyEntry() would hand back the winner's wallet
        // transaction. Scoping the constraint to (wallet_id, idempotency_key)
        // keeps idempotency keys unique per wallet instead of across the
        // whole ledger.
        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->dropUnique(['idempotency_key']);
            $table->unique(['wallet_id', 'idempotency_key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->dropUnique(['wallet_id', 'idempotency_key']);
            $table->unique('idempotency_key');
        });
    }
};
