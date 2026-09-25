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
        // Records the gateway reference a top-up was verified against, so
        // the same real-world charge can never be credited twice — even if
        // it's resubmitted under a different idempotency_key (which only
        // guards against retrying the *same* logical purchase attempt, not
        // reuse of someone else's already-consumed reference).
        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->string('payment_reference')->nullable()->unique()->after('idempotency_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->dropColumn('payment_reference');
        });
    }
};
