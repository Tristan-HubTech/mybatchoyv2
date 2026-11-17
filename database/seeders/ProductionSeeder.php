<?php

namespace Database\Seeders;

use App\Models\BusinessSetting;
use App\Models\TechnicalSetting;
use Illuminate\Database\Seeder;

class ProductionSeeder extends Seeder
{
    /**
     * Initialize required system reference roles and non-sensitive baseline configuration.
     * Safe for repeated execution and never creates default accounts, passwords, or demo data.
     */
    public function run(): void
    {
        $this->call(RoleSeeder::class);

        BusinessSetting::firstOrCreate(
            ['id' => 1],
            [
                'business_name' => config('app.name', 'MyBatchoy'),
                'session_timeout_minutes' => 20,
                'default_minimum_stock_level' => 0,
                'default_reorder_level' => 0,
            ]
        );

        TechnicalSetting::current();
    }
}

