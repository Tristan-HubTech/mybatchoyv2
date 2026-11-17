<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class OwnerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (app()->isProduction()) {
            throw new \RuntimeException('OwnerSeeder contains development credentials and cannot execute in production. Run "php artisan app:create-owner" instead.');
        }

        // Find the "owner" role (must exist from earlier migrations)
        $ownerRole = Role::where('name', 'owner')->first();
        if (! $ownerRole) {
            $this->command->error('Owner role not found. Ensure migrations have run.');
            return;
        }

        // Create the admin user
        User::firstOrCreate(
            ['username' => 'owner'],
            [
                'role_id' => $ownerRole->id,
                'name' => 'Owner Admin',
                'email' => 'owner@mybatchoy.local',
                'phone' => '+63 917 555 0100',
                'password' => Hash::make('Str0ng!Pass2024'),
                'is_active' => true,
            ]
        );

        $this->command?->info('Owner admin user seeded successfully.');
    }
}

