<?php

namespace Database\Seeders;

use App\Models\TokenBundle;
use Illuminate\Database\Seeder;

class TokenBundleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $bundles = [
            ['slug' => 'starter', 'name' => 'Starter', 'tokens' => 100, 'price' => 1.99, 'badge' => null, 'is_featured' => false],
            ['slug' => 'party', 'name' => 'Party', 'tokens' => 500, 'price' => 7.99, 'badge' => 'Popular', 'is_featured' => true],
            ['slug' => 'legend', 'name' => 'Legend', 'tokens' => 1500, 'price' => 19.99, 'badge' => 'Best value', 'is_featured' => false],
            ['slug' => 'whale', 'name' => 'Whale', 'tokens' => 5000, 'price' => 49.99, 'badge' => null, 'is_featured' => false],
        ];

        foreach ($bundles as $index => $bundle) {
            TokenBundle::query()->updateOrCreate(
                ['slug' => $bundle['slug']],
                [
                    'name' => $bundle['name'],
                    'tokens' => $bundle['tokens'],
                    'price' => $bundle['price'],
                    'currency' => 'USD',
                    'badge' => $bundle['badge'],
                    'gradient' => ['#7A1EFF', '#2D2A8F'],
                    'is_active' => true,
                    'is_featured' => $bundle['is_featured'],
                    'sort_order' => $index,
                ]
            );
        }
    }
}
