<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class StaffSeeder extends Seeder
{
    public function run(): void
    {
        $staffRole = Role::where('name', 'staff')->first();
        if (! $staffRole) {
            $this->command?->error('Staff role not found. Ensure RoleSeeder runs first.');
            return;
        }

        $staffMembers = [
            [
                'username' => 'staff_maria',
                'name' => 'Maria Santos',
                'email' => 'maria@mybatchoy.local',
                'phone' => '+63 917 555 0101',
                'password' => 'Batchoy!Staff1',
            ],
            [
                'username' => 'staff_juan',
                'name' => 'Juan Dela Cruz',
                'email' => 'juan@mybatchoy.local',
                'phone' => '+63 917 555 0102',
                'password' => 'Batchoy!Staff2',
            ],
            [
                'username' => 'staff_elena',
                'name' => 'Elena Reyes',
                'email' => 'elena@mybatchoy.local',
                'phone' => '+63 917 555 0103',
                'password' => 'Batchoy!Staff3',
            ],
        ];

        foreach ($staffMembers as $member) {
            User::firstOrCreate(
                ['username' => $member['username']],
                [
                    'role_id' => $staffRole->id,
                    'name' => $member['name'],
                    'email' => $member['email'],
                    'phone' => $member['phone'],
                    'password' => Hash::make($member['password']),
                    'is_active' => true,
                ]
            );
        }

        $this->command?->info('3 Staff accounts seeded successfully.');
    }
}

