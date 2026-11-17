<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessAccessRequest extends Model
{
    use HasFactory;

    public const SCOPES = [
        'inventory' => 'Inventory Records & Stock Alerts',
        'transactions' => 'Inventory Transaction History',
        'purchases' => 'Purchasing Records & Supplier Pricing',
        'suppliers' => 'Supplier Directory & Contacts',
        'reports' => 'Business & Financial Reports',
        'audit_logs' => 'Business Audit History',
    ];

    protected $fillable = [
        'system_admin_id',
        'scope',
        'reason',
        'duration_minutes',
        'status',
        'reviewed_by',
        'review_notes',
        'reviewed_at',
        'expires_at',
        'revoked_at',
        'last_accessed_at',
    ];

    protected $casts = [
        'duration_minutes' => 'integer',
        'reviewed_at' => 'datetime',
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
        'last_accessed_at' => 'datetime',
    ];

    public function systemAdmin()
    {
        return $this->belongsTo(User::class, 'system_admin_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopeActiveGrants(Builder $query, int $systemAdminId, ?string $scope = null): Builder
    {
        return $query->where('system_admin_id', $systemAdminId)
            ->where('status', 'approved')
            ->whereNull('revoked_at')
            ->whereNotNull('expires_at')
            ->where('expires_at', '>', now())
            ->when($scope !== null, fn (Builder $q) => $q->where('scope', $scope));
    }

    public function isCurrentlyActive(): bool
    {
        return $this->status === 'approved'
            && $this->revoked_at === null
            && $this->expires_at !== null
            && $this->expires_at->isFuture();
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired'
            || ($this->status === 'approved' && $this->expires_at !== null && $this->expires_at->isPast());
    }

    public function effectiveStatus(): string
    {
        if ($this->status === 'approved') {
            if ($this->revoked_at !== null) {
                return 'revoked';
            }
            if ($this->expires_at !== null && $this->expires_at->isPast()) {
                return 'expired';
            }
        }

        return $this->status;
    }

    public function scopeLabel(): string
    {
        return self::SCOPES[$this->scope] ?? ucfirst(str_replace('_', ' ', (string) $this->scope));
    }
}

