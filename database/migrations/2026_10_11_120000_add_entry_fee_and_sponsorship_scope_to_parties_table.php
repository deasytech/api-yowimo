<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parties', function (Blueprint $table) {
            $table->unsignedInteger('entry_fee')->default(0)->after('max_players');
            $table->string('sponsorship_scope')->nullable()->after('entry_fee');
        });
    }

    public function down(): void
    {
        Schema::table('parties', function (Blueprint $table) {
            $table->dropColumn(['entry_fee', 'sponsorship_scope']);
        });
    }
};
