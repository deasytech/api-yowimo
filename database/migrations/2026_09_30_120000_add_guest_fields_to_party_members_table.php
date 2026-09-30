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
        Schema::table('party_members', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
            $table->string('guest_name')->nullable()->after('user_id');
            $table->string('guest_emoji')->nullable()->after('guest_name');
            $table->string('join_mode')->nullable()->after('guest_emoji');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('party_members', function (Blueprint $table) {
            $table->dropColumn(['guest_name', 'guest_emoji', 'join_mode']);
            $table->foreignId('user_id')->nullable(false)->change();
        });
    }
};
