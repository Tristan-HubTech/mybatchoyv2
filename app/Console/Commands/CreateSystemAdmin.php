<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use App\Services\TechnicalAuditLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class CreateSystemAdmin extends Command
{
    protected $signature = 'app:create-system-admin';

    protected $description = 'Create the first protected System Admin (Developer) account via technical CLI';

    public function handle(): int
    {
        if (User::whereHas('role', fn ($q) => $q->where('name', 'system_admin'))->exists()) {
            $this->error('A System Admin account already exists. Additional System Admins must be created through the authenticated technical admin workflow.');

            return self::FAILURE;
        }

        $attributes = [
            'name' => trim((string) $this->ask('Full name')),
            'username' => trim((string) $this->ask('Username')),
            'email' => strtolower(trim((string) $this->ask('Email address'))),
            'password' => $this->secret('Password'),
            'password_confirmation' => $this->secret('Confirm password'),
        ];

        $validator = Validator::make($attributes, [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('users', 'username')],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', Password::min(12)->letters()->mixedCase()->numbers()->symbols(), 'confirmed'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $role = Role::firstOrCreate(
            ['name' => 'system_admin'],
            ['label' => 'System Admin (Developer)']
        );

        $created = DB::transaction(function () use ($role, $attributes): ?User {
            $lockedRole = Role::whereKey($role->id)->lockForUpdate()->firstOrFail();

            if (User::where('role_id', $lockedRole->id)->exists()) {
                return null;
            }

            return User::create([
                'role_id' => $lockedRole->id,
                'name' => $attributes['name'],
                'username' => $attributes['username'],
                'email' => $attributes['email'],
                'password' => Hash::make($attributes['password']),
                'is_active' => true,
            ]);
        });

        if (!$created) {
            $this->error('A System Admin account already exists. Initial technical setup is closed.');

            return self::FAILURE;
        }

        TechnicalAuditLogger::record(
            null,
            $created->id,
            'account_admin',
            'system_admin_created_cli',
            "Initial System Admin account {$created->username} created via protected CLI.",
            User::class,
            $created->id
        );

        $this->info('System Admin (Developer) account created successfully.');

        return self::SUCCESS;
    }
}

