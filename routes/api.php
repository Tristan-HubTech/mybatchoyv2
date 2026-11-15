<?php

use App\Http\Controllers\Api\V1\AuditLogApiController;
use App\Http\Controllers\Api\V1\AuthApiController;
use App\Http\Controllers\Api\V1\DashboardApiController;
use App\Http\Controllers\Api\V1\InventoryApiController;
use App\Http\Controllers\Api\V1\ProfileApiController;
use App\Http\Controllers\Api\V1\PurchaseApiController;
use App\Http\Controllers\Api\V1\ReportApiController;
use App\Http\Controllers\Api\V1\SettingsApiController;
use App\Http\Controllers\Api\V1\StaffApiController;
use App\Http\Controllers\Api\V1\SupplierApiController;
use App\Http\Controllers\Api\V1\TransactionApiController;
use App\Http\Controllers\BackupController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    // Public Authentication & Password Recovery
    Route::post('/auth/login', [AuthApiController::class, 'login'])->name('auth.login');
    Route::post('/auth/forgot-password', [AuthApiController::class, 'forgotPassword'])->name('auth.forgotPassword');
    Route::post('/auth/reset-password', [AuthApiController::class, 'resetPassword'])->name('auth.resetPassword');

    // Authenticated API Routes (Owner & Staff)
    Route::middleware(['auth:sanctum', 'active'])->group(function () {
        Route::get('/auth/me', [AuthApiController::class, 'me'])->name('auth.me');
        Route::post('/auth/logout', [AuthApiController::class, 'logout'])->name('auth.logout');

        // Dashboard (role-aware response)
        Route::get('/dashboard', [DashboardApiController::class, 'overview'])->name('dashboard');

        // Profile & Password
        Route::get('/profile', [ProfileApiController::class, 'show'])->name('profile.show');
        Route::put('/profile', [ProfileApiController::class, 'update'])->middleware('idempotent')->name('profile.update');
        Route::put('/profile/password', [ProfileApiController::class, 'updatePassword'])->middleware('idempotent')->name('profile.password');

        // Inventory Viewing (Owner & Staff)
        Route::get('/inventory', [InventoryApiController::class, 'index'])->name('inventory.index');
        Route::get('/inventory/{item}', [InventoryApiController::class, 'show'])->name('inventory.show');

        // Stock Transactions (Owner & Staff — Staff history scoped to own records)
        Route::get('/transactions', [TransactionApiController::class, 'index'])->name('transactions.index');
        Route::post('/transactions/stock-in', [TransactionApiController::class, 'stockIn'])->middleware('idempotent')->name('transactions.stockIn');
        Route::post('/transactions/stock-out', [TransactionApiController::class, 'stockOut'])->middleware('idempotent')->name('transactions.stockOut');
        Route::post('/transactions/adjustment', [TransactionApiController::class, 'adjustment'])->middleware('idempotent')->name('transactions.adjustment');
        Route::post('/transactions/spoilage', [TransactionApiController::class, 'spoilage'])->middleware('idempotent')->name('transactions.spoilage');

        // Owner-only Routes
        Route::middleware('role:owner')->group(function () {
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
            Route::get('/backup/download', [BackupController::class, 'downloadBackup'])->name('backup.download');
        });
    });
});
