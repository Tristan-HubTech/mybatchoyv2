<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(ProductionSeeder::class);

        if (app()->isProduction()) {
            $this->command?->warn(
                'Production environment detected: skipping demo users and sample inventory. '
                . 'Use "php artisan app:create-owner" and "php artisan app:create-system-admin" to provision initial accounts.'
            );

            return;
        }

        $this->call([
            OwnerSeeder::class,
            StaffSeeder::class,
            InventorySeeder::class,
            TestDataSeeder::class,
        ]);
    }
}
