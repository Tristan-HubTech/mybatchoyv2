<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'owner', 'label' => 'Owner / Admin'],
            ['name' => 'staff', 'label' => 'Staff'],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate($role);
        }

        $ownerRole = Role::where('name', 'owner')->first();
        $staffRole = Role::where('name', 'staff')->first();

        User::updateOrCreate(
            ['email' => 'owner@mybatchoy.com'],
            [
                'role_id' => $ownerRole->id,
                'name' => 'Owner Admin',
                'username' => 'owneradmin',
                'phone' => '09170000001',
                'password' => Hash::make('admin123'),
                'mfa_enabled' => false,
                'is_active' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'staff@mybatchoy.com'],
            [
                'role_id' => $staffRole->id,
                'name' => 'Staff Member',
                'username' => 'staffuser',
                'phone' => '09170000002',
                'password' => Hash::make('staff123'),
                'mfa_enabled' => false,
                'is_active' => true,
            ]
        );
    }
}
