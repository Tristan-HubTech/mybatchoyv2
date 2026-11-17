<?php

use App\Http\Controllers\Api\V1\AuditLogApiController;
use App\Http\Controllers\Api\V1\AuthApiController;
use App\Http\Controllers\Api\V1\BusinessAccessRequestApiController;
use App\Http\Controllers\Api\V1\DashboardApiController;
use App\Http\Controllers\Api\V1\InventoryApiController;
use App\Http\Controllers\Api\V1\NotificationApiController;
use App\Http\Controllers\Api\V1\ProfileApiController;
use App\Http\Controllers\Api\V1\PurchaseApiController;
use App\Http\Controllers\Api\V1\ReportApiController;
use App\Http\Controllers\Api\V1\SettingsApiController;
use App\Http\Controllers\Api\V1\StaffApiController;
use App\Http\Controllers\Api\V1\StockAdjustmentRequestApiController;
use App\Http\Controllers\Api\V1\SupplierAccountApiController;
use App\Http\Controllers\Api\V1\SupplierApiController;
use App\Http\Controllers\Api\V1\SupplierPortalApiController;
use App\Http\Controllers\Api\V1\SystemAdminApiController;
use App\Http\Controllers\Api\V1\TransactionApiController;
use App\Http\Controllers\BackupController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    // Public Health & Authentication / Password Recovery
    Route::get('/health', fn () => response()->json(['ok' => true, 'service' => 'mybatchoy-api', 'version' => 'v1']))->name('health');
    Route::post('/auth/login', [AuthApiController::class, 'login'])->name('auth.login');
    Route::post('/auth/forgot-password', [AuthApiController::class, 'forgotPassword'])->name('auth.forgotPassword');
    Route::post('/auth/reset-password', [AuthApiController::class, 'resetPassword'])->name('auth.resetPassword');

    // Authenticated API Routes (Owner & Staff)
    Route::middleware(['auth:sanctum', 'active'])->group(function () {
        Route::get('/auth/me', [AuthApiController::class, 'me'])->name('auth.me');
        Route::post('/auth/logout', [AuthApiController::class, 'logout'])->name('auth.logout');

        // Dashboard (role-aware response)
        Route::get('/dashboard', [DashboardApiController::class, 'overview'])->name('dashboard');
        Route::get('/notifications', [NotificationApiController::class, 'index'])->name('notifications.index');

        // Profile & Password
        Route::get('/profile', [ProfileApiController::class, 'show'])->name('profile.show');
        Route::put('/profile', [ProfileApiController::class, 'update'])->middleware('idempotent')->name('profile.update');
        Route::put('/profile/password', [ProfileApiController::class, 'updatePassword'])->middleware('idempotent')->name('profile.password');

        // Inventory & Stock Operations (Owner & Staff ONLY — blocked for System Admin & Supplier)
        Route::middleware('role:owner,staff')->group(function () {
            Route::get('/inventory', [InventoryApiController::class, 'index'])->name('inventory.index');
            Route::get('/inventory/{item}', [InventoryApiController::class, 'show'])->name('inventory.show');

            Route::get('/transactions', [TransactionApiController::class, 'index'])->name('transactions.index');
            Route::post('/transactions/stock-in', [TransactionApiController::class, 'stockIn'])->middleware('idempotent')->name('transactions.stockIn');
            Route::post('/transactions/stock-out', [TransactionApiController::class, 'stockOut'])->middleware('idempotent')->name('transactions.stockOut');
            Route::post('/transactions/spoilage', [TransactionApiController::class, 'spoilage'])->middleware('idempotent')->name('transactions.spoilage');

            Route::get('/adjustment-requests', [StockAdjustmentRequestApiController::class, 'index'])->name('adjustments.index');
            Route::post('/adjustment-requests', [StockAdjustmentRequestApiController::class, 'store'])->middleware('idempotent')->name('adjustments.store');
        });

        // Owner-only Routes
        Route::middleware('role:owner')->group(function () {
            Route::post('/transactions/adjustment', [TransactionApiController::class, 'adjustment'])->middleware('idempotent')->name('transactions.adjustment');

            // Stock Adjustment Approvals (Owner only)
            Route::post('/adjustment-requests/{adjustmentRequest}/approve', [StockAdjustmentRequestApiController::class, 'approve'])->middleware('idempotent')->name('adjustments.approve');
            Route::post('/adjustment-requests/{adjustmentRequest}/reject', [StockAdjustmentRequestApiController::class, 'reject'])->middleware('idempotent')->name('adjustments.reject');

            // Supplier User Accounts (Owner only)
            Route::get('/supplier-accounts', [SupplierAccountApiController::class, 'index'])->name('supplier-accounts.index');
            Route::post('/supplier-accounts', [SupplierAccountApiController::class, 'store'])->middleware('idempotent')->name('supplier-accounts.store');
            Route::patch('/supplier-accounts/{supplierAccount}/status', [SupplierAccountApiController::class, 'updateStatus'])->middleware('idempotent')->name('supplier-accounts.status');

            // Exceptional System Admin Access Authorizations (Owner only)
            Route::get('/access-requests', [BusinessAccessRequestApiController::class, 'index'])->name('access.index');
            Route::post('/access-requests/{accessRequest}/approve', [BusinessAccessRequestApiController::class, 'approve'])->middleware('idempotent')->name('access.approve');
            Route::post('/access-requests/{accessRequest}/reject', [BusinessAccessRequestApiController::class, 'reject'])->middleware('idempotent')->name('access.reject');
            Route::post('/access-requests/{accessRequest}/revoke', [BusinessAccessRequestApiController::class, 'revoke'])->middleware('idempotent')->name('access.revoke');
            // Inventory Management
            Route::post('/inventory', [InventoryApiController::class, 'store'])->middleware('idempotent')->name('inventory.store');
            Route::put('/inventory/{item}', [InventoryApiController::class, 'update'])->middleware('idempotent')->name('inventory.update');

            // Suppliers
            Route::get('/suppliers', [SupplierApiController::class, 'index'])->name('suppliers.index');
            Route::get('/suppliers/{supplier}', [SupplierApiController::class, 'show'])->name('suppliers.show');
            Route::post('/suppliers', [SupplierApiController::class, 'store'])->middleware('idempotent')->name('suppliers.store');
            Route::put('/suppliers/{supplier}', [SupplierApiController::class, 'update'])->middleware('idempotent')->name('suppliers.update');

            // Purchases
            Route::get('/purchases', [PurchaseApiController::class, 'index'])->name('purchases.index');
            Route::get('/purchases/{purchase}', [PurchaseApiController::class, 'show'])->name('purchases.show');
            Route::post('/purchases', [PurchaseApiController::class, 'store'])->middleware('idempotent')->name('purchases.store');

            // Reports & CSV Export
            Route::get('/reports', [ReportApiController::class, 'index'])->name('reports.index');
            Route::get('/reports/export', [ReportApiController::class, 'exportCsv'])->name('reports.export');

            // Staff Management
            Route::get('/staff', [StaffApiController::class, 'index'])->name('staff.index');
            Route::post('/staff', [StaffApiController::class, 'store'])->middleware('idempotent')->name('staff.store');
            Route::put('/staff/{staff}', [StaffApiController::class, 'update'])->middleware('idempotent')->name('staff.update');
            Route::patch('/staff/{staff}/status', [StaffApiController::class, 'updateStatus'])->middleware('idempotent')->name('staff.status');
            Route::put('/staff/{staff}/password', [StaffApiController::class, 'resetPassword'])->middleware('idempotent')->name('staff.password');

            // Audit Logs
            Route::get('/audit-logs', [AuditLogApiController::class, 'index'])->name('audit.index');

            // Business Settings & Inventory Config
            Route::get('/settings', [SettingsApiController::class, 'index'])->name('settings.index');
            Route::put('/settings', [SettingsApiController::class, 'update'])->middleware('idempotent')->name('settings.update');
            Route::post('/settings/security/mfa-challenge', [SettingsApiController::class, 'sendMfaChallenge'])->name('settings.mfa.challenge');
            Route::post('/settings/security/sessions/{user}/revoke', [SettingsApiController::class, 'revokeActiveSession'])->name('settings.sessions.revoke');
            Route::post('/settings/categories', [SettingsApiController::class, 'storeCategory'])->middleware('idempotent')->name('settings.categories.store');
            Route::put('/settings/categories/{category}', [SettingsApiController::class, 'updateCategory'])->middleware('idempotent')->name('settings.categories.update');
            Route::patch('/settings/categories/{category}/status', [SettingsApiController::class, 'updateCategoryStatus'])->middleware('idempotent')->name('settings.categories.status');
            Route::post('/settings/units', [SettingsApiController::class, 'storeUnit'])->middleware('idempotent')->name('settings.units.store');
            Route::put('/settings/units/{unit}', [SettingsApiController::class, 'updateUnit'])->middleware('idempotent')->name('settings.units.update');
            Route::patch('/settings/units/{unit}/status', [SettingsApiController::class, 'updateUnitStatus'])->middleware('idempotent')->name('settings.units.status');

            // Database Backup Info & Download
            Route::get('/backup', [SettingsApiController::class, 'backupInfo'])->name('backup.index');
            Route::post('/backup/download', [BackupController::class, 'downloadBackup'])->name('backup.download');
        });

        // System Admin Technical API Routes (no business data without active Owner grant)
        Route::middleware('role:system_admin')->prefix('sysadmin')->name('sysadmin.')->group(function () {
            Route::get('/health', [SystemAdminApiController::class, 'health'])->name('health');
            Route::get('/security', [SystemAdminApiController::class, 'security'])->name('security');
            Route::put('/config', [SystemAdminApiController::class, 'updateConfig'])->middleware('idempotent')->name('config.update');
            Route::get('/accounts', [SystemAdminApiController::class, 'accounts'])->name('accounts.index');
            Route::post('/accounts', [SystemAdminApiController::class, 'storeAccount'])->middleware('idempotent')->name('accounts.store');
            Route::get('/access-requests', [SystemAdminApiController::class, 'accessRequests'])->name('access.index');
            Route::post('/access-requests', [SystemAdminApiController::class, 'storeAccessRequest'])->middleware('idempotent')->name('access.store');
            Route::get('/access-requests/{accessRequest}/view', [SystemAdminApiController::class, 'viewGrantedBusinessData'])->name('access.view');
            Route::post('/backup/download', [BackupController::class, 'downloadBackup'])->name('backup.download');
        });

        Route::middleware('permission:technical_administration')->group(function () {
            Route::put('/technical-config', [SystemAdminApiController::class, 'updateConfig'])->middleware('idempotent')->name('technical.config.update');
        });

        // Supplier Portal API Routes (strictly scoped to own supplier_id)
        Route::middleware('role:supplier')->prefix('supplier')->name('supplier.')->group(function () {
            Route::get('/orders', [SupplierPortalApiController::class, 'orders'])->name('orders.index');
            Route::post('/orders/{purchase}/confirm', [SupplierPortalApiController::class, 'confirm'])->middleware('idempotent')->name('orders.confirm');
            Route::post('/orders/{purchase}/reject', [SupplierPortalApiController::class, 'reject'])->middleware('idempotent')->name('orders.reject');
            Route::patch('/orders/{purchase}/delivery', [SupplierPortalApiController::class, 'updateDelivery'])->middleware('idempotent')->name('orders.delivery');
            Route::get('/profile', [SupplierPortalApiController::class, 'profile'])->name('profile.show');
            Route::put('/profile', [SupplierPortalApiController::class, 'updateProfile'])->middleware('idempotent')->name('profile.update');
        });
    });
});
