<?php

use App\Models\User;
use App\Services\Referrals\ReferralCodeGenerator;
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
        Schema::table('users', function (Blueprint $table) {
            $table->string('referral_code')->nullable()->unique()->after('username');
            $table->foreignId('referred_by_user_id')->nullable()->after('referral_code')
                ->constrained('users')->nullOnDelete();
        });

        // Backfill existing users so everyone has a shareable code going
        // forward, not just accounts created after this migration. Keyed by
        // id (not an offset), so updating matched rows as we go — which
        // removes them from the next page's WHERE — can't skip any.
        $generator = app(ReferralCodeGenerator::class);

        User::withTrashed()->whereNull('referral_code')->eachById(function (User $user) use ($generator) {
            $user->update(['referral_code' => $generator->generate()]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('referred_by_user_id');
            $table->dropColumn('referral_code');
        });
    }
};
