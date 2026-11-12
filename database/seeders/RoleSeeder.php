<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

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
    }
}
