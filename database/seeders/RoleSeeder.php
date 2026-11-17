<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'system_admin', 'label' => 'System Admin (Developer)'],
            ['name' => 'owner', 'label' => 'Owner / Admin'],
            ['name' => 'staff', 'label' => 'Staff'],
            ['name' => 'supplier', 'label' => 'Supplier'],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role['name']], ['label' => $role['label']]);
        }
    }
}
