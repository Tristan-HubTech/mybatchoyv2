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
        // Find the "owner" role (must exist from earlier migrations)
        $ownerRole = Role::where('name', 'owner')->first();
        if (! $ownerRole) {
            $this->command->error('Owner role not found. Ensure migrations have run.');
            return;
        }

        // Create the admin user
        User::create([
            'role_id'   => $ownerRole->id,
            'name'      => 'Owner Admin',
            'username'  => 'owner',
            'email'     => 'owner@mybatchoy.local',
            // Suggested strong password (you can change it later)
            'password'  => Hash::make('Str0ng!Pass2024'),
            'is_active'=> true,
        ]);

        $this->command->info('Owner admin user created successfully.');
    }
}

