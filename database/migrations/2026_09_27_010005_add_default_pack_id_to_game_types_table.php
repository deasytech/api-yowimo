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
        Schema::table('game_types', function (Blueprint $table) {
            $table->foreignId('default_pack_id')
                ->nullable()
                ->after('image_url')
                ->constrained('packs')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('game_types', function (Blueprint $table) {
            $table->dropConstrainedForeignId('default_pack_id');
        });
    }
};
