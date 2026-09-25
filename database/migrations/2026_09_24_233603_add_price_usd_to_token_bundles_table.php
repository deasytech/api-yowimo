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
        // The app is a Nigerian company: price/currency is the default
        // price (NGN going forward), charged to everyone unless the buyer's
        // profile is confirmed to be outside Nigeria and this bundle has a
        // price_usd set — per-market pricing set by an admin, not a live
        // currency conversion. See TokenBundle::priceFor().
        Schema::table('token_bundles', function (Blueprint $table) {
            $table->decimal('price_usd', 10, 2)->nullable()->after('currency');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('token_bundles', function (Blueprint $table) {
            $table->dropColumn('price_usd');
        });
    }
};
