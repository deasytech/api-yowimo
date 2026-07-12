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
        Schema::create('packs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_type_id')->nullable()->constrained()->nullOnDelete();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('emoji')->nullable();
            $table->string('tag')->nullable();
            $table->string('category');
            $table->text('description')->nullable();
            $table->unsignedInteger('price')->default(0);
            $table->unsignedInteger('truths_count')->default(0);
            $table->unsignedInteger('dares_count')->default(0);
            $table->unsignedInteger('cards_count')->default(0);
            $table->string('cover_image_url')->nullable();
            $table->json('gradient')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['category', 'is_active']);
            $table->index(['is_featured', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('packs');
    }
};
