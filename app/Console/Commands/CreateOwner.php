<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class CreateOwner extends Command
{
    protected $signature = 'app:create-owner';

    protected $description = 'Interactively create the first Owner/Admin account';

    public function handle(): int
    {
        if ($this->ownerExists()) {
            $this->error('An Owner/Admin account already exists. Initial setup is closed.');

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
            ['name' => 'owner'],
            ['label' => 'Owner / Admin']
        );

        $created = DB::transaction(function () use ($role, $attributes): bool {
            $lockedRole = Role::whereKey($role->id)->lockForUpdate()->firstOrFail();

            if (User::where('role_id', $lockedRole->id)->exists()) {
                return false;
            }

            User::create([
                'role_id' => $lockedRole->id,
                'name' => $attributes['name'],
                'username' => $attributes['username'],
                'email' => $attributes['email'],
                'password' => Hash::make($attributes['password']),
                'is_active' => true,
            ]);

            return true;
        });

        if (!$created) {
            $this->error('An Owner/Admin account already exists. Initial setup is closed.');
            return self::FAILURE;
        }

        $this->info('Initial Owner/Admin account created successfully.');

        return self::SUCCESS;
    }

    private function ownerExists(): bool
    {
        return User::whereHas('role', fn ($query) => $query->where('name', 'owner'))->exists();
    }
}
