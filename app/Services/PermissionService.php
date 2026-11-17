<?php

namespace App\Services;

use App\Models\BusinessAccessRequest;
use App\Models\TechnicalSetting;
use App\Models\User;

class PermissionService
{
    public const GROUP_TECHNICAL_ADMIN = 'technical_administration';
    public const GROUP_BUSINESS_OWNERSHIP = 'business_ownership';
    public const GROUP_INVENTORY_OPS = 'inventory_operations';
    public const GROUP_PURCHASING_SUPPLIERS = 'purchasing_suppliers';
    public const GROUP_BUSINESS_REPORTING = 'business_reporting';
    public const GROUP_ACCOUNT_MANAGEMENT = 'account_management';
    public const GROUP_SECURITY_MANAGEMENT = 'security_management';

    /**
     * Explicit separation of technical and business permission groups.
     *
     * @var array<string, array{label: string, roles: array<int, string>, description: string}>
     */
    public const GROUPS = [
        self::GROUP_TECHNICAL_ADMIN => [
            'label' => 'Technical Administration',
            'roles' => ['system_admin'],
            'description' => 'Application health, diagnostics, maintenance configuration, and technical troubleshooting.',
        ],
        self::GROUP_BUSINESS_OWNERSHIP => [
            'label' => 'Business Ownership',
            'roles' => ['owner'],
            'description' => 'Full business authority, transaction approvals, business settings, and authorizing exceptional technical access.',
        ],
        self::GROUP_INVENTORY_OPS => [
            'label' => 'Inventory Operations',
            'roles' => ['owner', 'staff'],
            'description' => 'Stock counts, spoilage, movements, and inventory item operations.',
        ],
        self::GROUP_PURCHASING_SUPPLIERS => [
            'label' => 'Purchasing & Supplier Management',
            'roles' => ['owner', 'supplier'],
            'description' => 'Owner purchasing/supplier control and scoped external supplier portal fulfillment.',
        ],
        self::GROUP_BUSINESS_REPORTING => [
            'label' => 'Business Reporting',
            'roles' => ['owner'],
            'description' => 'Financial, valuation, purchasing cost, and executive analytics reports.',
        ],
        self::GROUP_ACCOUNT_MANAGEMENT => [
            'label' => 'Account Management',
            'roles' => ['owner', 'system_admin'],
            'description' => 'Owner controls Staff/Supplier accounts; System Admin controls protected technical admin accounts.',
        ],
        self::GROUP_SECURITY_MANAGEMENT => [
            'label' => 'Security & Recovery Management',
            'roles' => ['system_admin', 'owner'],
            'description' => 'System Admin manages technical security, backups, and auth events; Owner oversees business audit logs and business session recovery.',
        ],
    ];

    public static function allows(?User $user, string $group): bool
    {
        if (!$user || !$user->is_active) {
            return false;
        }

        $roleName = $user->roleName();
        if (!isset(self::GROUPS[$group])) {
            return false;
        }

        if ($group === self::GROUP_TECHNICAL_ADMIN && $roleName === 'owner') {
            return ActiveSessionManager::hasTable('technical_settings')
                && (bool) TechnicalSetting::current()->owner_can_edit_technical;
        }

        return in_array($roleName, self::GROUPS[$group]['roles'], true);
    }

    public static function hasActiveBusinessAccessGrant(?User $user, string $scope): ?BusinessAccessRequest
    {
        if (!$user || !$user->is_active || !$user->isSystemAdmin()) {
            return null;
        }

        if (!ActiveSessionManager::hasTable('business_access_requests')) {
            return null;
        }

        return BusinessAccessRequest::activeGrants((int) $user->id, $scope)
            ->latest('expires_at')
            ->first();
    }

    public static function expireElapsedGrants(): int
    {
        if (!ActiveSessionManager::hasTable('business_access_requests')) {
            return 0;
        }

        return BusinessAccessRequest::where('status', 'approved')
            ->whereNull('revoked_at')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->update(['status' => 'expired']);
    }

    /**
     * @return array<int, string>
     */
    public static function permissionGroupsForRole(?string $roleName): array
    {
        $matched = [];
        foreach (self::GROUPS as $key => $meta) {
            if (in_array($roleName, $meta['roles'], true)) {
                $matched[] = $key;
            }
        }

        return $matched;
    }
}

