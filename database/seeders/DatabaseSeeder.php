<?php

namespace Database\Seeders;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['clerk_user_id' => 'user_super_admin'],
            [
                'username' => 'superadmin',
                'display_name' => 'Super Admin',
                'email' => 'superadmin@yowimo.com',
                'status' => UserStatus::Active,
            ]
        );

        $this->call([
            GameTypeSeeder::class,
            PackSeeder::class,
            TokenBundleSeeder::class,
            PartySeeder::class,
        ]);
    }
}
