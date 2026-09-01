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
        // Append-only ledger: the source of truth for user XP totals. Rows are
        // never updated or deleted (enforced in the XpTransaction model).
        Schema::create('xp_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->unsignedInteger('amount');
            $table->foreignId('game_session_id')->nullable()->constrained()->nullOnDelete();
            $table->nullableMorphs('reference');
            $table->string('idempotency_key')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['user_id', 'idempotency_key']);
            $table->index('game_session_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('xp_transactions');
    }
};
