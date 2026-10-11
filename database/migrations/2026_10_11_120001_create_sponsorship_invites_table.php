<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sponsorship_invites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('party_id')->constrained('parties')->cascadeOnDelete();
            $table->string('scope');
            $table->unsignedInteger('amount');
            $table->string('status')->default('pending');
            $table->string('token')->unique();
            $table->foreignId('sponsor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();
            $table->index(['party_id', 'scope', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sponsorship_invites');
    }
};
