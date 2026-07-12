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
        Schema::create('pack_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pack_id')->constrained()->cascadeOnDelete();
            $table->string('kind');
            $table->text('text');
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_preview')->default(false);
            $table->timestamps();

            $table->index(['pack_id', 'is_preview']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pack_cards');
    }
};
