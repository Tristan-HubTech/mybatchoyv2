<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'role_id',
        'supplier_id',
        'name',
        'username',
        'email',
        'phone',
        'password',
        'mfa_enabled',
        'mfa_secret',
        'is_active',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'mfa_secret',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'password' => 'hashed',
        'mfa_enabled' => 'boolean',
        'mfa_secret' => 'encrypted',
        'is_active' => 'boolean',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function activeSession()
    {
        return $this->hasOne(UserActiveSession::class);
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }

    public function roleName(): ?string
    {
        if ($this->relationLoaded('role')) {
            return $this->role?->name;
        }

        return Role::cachedNameById($this->role_id);
    }

    public function isSystemAdmin(): bool
    {
        return $this->roleName() === 'system_admin';
    }

    public function isOwner(): bool
    {
        return $this->roleName() === 'owner';
    }

    public function isStaff(): bool
    {
        return $this->roleName() === 'staff';
    }

    public function isSupplier(): bool
    {
        return $this->roleName() === 'supplier';
    }

    public function dashboardRouteName(): string
    {
        return match ($this->roleName()) {
            'system_admin' => 'sysadmin.dashboard',
            'owner' => 'owner.dashboard',
            'supplier' => 'supplier.dashboard',
            default => 'staff.dashboard',
        };
    }

    public function hasPermissionGroup(string $group): bool
    {
        return \App\Services\PermissionService::allows($this, $group);
    }
}
