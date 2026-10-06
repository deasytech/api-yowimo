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
        Schema::create('ad_reward_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            // sha256 of the plaintext token handed to the client — never the
            // plaintext itself, same posture as a password-reset token: a DB
            // read alone never hands out a live, spendable session token.
            $table->string('token_hash')->unique();
            $table->string('status')->default('pending');
            $table->unsignedInteger('reward_amount')->default(1);
            // Google's own transaction_id — a replay guard independent of
            // token_hash, since a replayed SSV callback for an already-resolved
            // session wouldn't otherwise collide on anything.
            $table->string('ad_network_transaction_id')->nullable()->unique();
            $table->foreignId('wallet_transaction_id')->nullable()->constrained('wallet_transactions')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('credited_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status', 'credited_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ad_reward_sessions');
    }
};
