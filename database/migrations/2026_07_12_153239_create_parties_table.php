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
        Schema::create('parties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('host_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('game_type_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('pack_id')->nullable()->constrained()->nullOnDelete();
            $table->string('room_code', 6)->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('mode');
            $table->string('visibility')->default('private');
            $table->string('status')->default('draft');
            $table->unsignedInteger('max_players')->default(8);
            $table->unsignedInteger('players_count')->default(1);
            $table->unsignedInteger('likes_count')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->json('location')->nullable();
            $table->json('tags')->nullable();
            $table->string('cover_image_url')->nullable();
            $table->json('gradient')->nullable();
            $table->boolean('is_sponsored')->default(false);
            $table->string('sponsor_name')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['visibility', 'status']);
            $table->index(['mode']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parties');
    }
};
