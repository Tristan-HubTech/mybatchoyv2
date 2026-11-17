<?php

use App\Http\Controllers\AppDownloadController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\BusinessAccessRequestController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventoryItemController;
use App\Http\Controllers\InventorySettingsController;
use App\Http\Controllers\OwnerSupplierAccountController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\StockAdjustmentRequestController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\SupplierPortalController;
use App\Http\Controllers\SystemAdminController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index'])->name('home');
Route::get('/home', [DashboardController::class, 'index']);
Route::get('/download/mybatchoy.apk', [AppDownloadController::class, 'downloadApk'])->name('app.download');
Route::get('/media/business-logo', [AppDownloadController::class, 'serveLogo'])->name('media.logo');
Route::redirect('/about', '/login')->name('about');
Route::redirect('/contact', '/login')->name('contact');
Route::redirect('/menu', '/login')->name('menu');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);

    Route::get('/forgot-password', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetCode'])->name('password.email');
    Route::get('/reset-password', [ForgotPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [ForgotPasswordController::class, 'resetPassword'])->name('password.update');
});

Route::middleware(['auth', 'active', 'auth.session'])->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    Route::middleware('role:owner')->group(function () {
        Route::get('/owner/dashboard', [DashboardController::class, 'ownerDashboard'])->name('owner.dashboard');

        Route::get('/owner/inventory', [InventoryItemController::class, 'index'])->name('owner.inventory.index');
        Route::get('/owner/inventory/create', [InventoryItemController::class, 'create'])->name('owner.inventory.create');
        Route::post('/owner/inventory', [InventoryItemController::class, 'store'])->name('owner.inventory.store');
        Route::get('/owner/inventory/{item}/edit', [InventoryItemController::class, 'edit'])->name('owner.inventory.edit');
        Route::put('/owner/inventory/{item}', [InventoryItemController::class, 'update'])->name('owner.inventory.update');

        Route::get('/owner/suppliers', [SupplierController::class, 'index'])->name('owner.suppliers.index');
        Route::get('/owner/suppliers/create', [SupplierController::class, 'create'])->name('owner.suppliers.create');
        Route::post('/owner/suppliers', [SupplierController::class, 'store'])->name('owner.suppliers.store');
        Route::get('/owner/suppliers/{supplier}/edit', [SupplierController::class, 'edit'])->name('owner.suppliers.edit');
        Route::put('/owner/suppliers/{supplier}', [SupplierController::class, 'update'])->name('owner.suppliers.update');

        Route::get('/owner/purchases', [PurchaseController::class, 'index'])->name('owner.purchases.index');
        Route::get('/owner/purchases/create', [PurchaseController::class, 'create'])->name('owner.purchases.create');
        Route::post('/owner/purchases', [PurchaseController::class, 'store'])->name('owner.purchases.store');
        Route::post('/owner/purchases/{purchase}/receive', [PurchaseController::class, 'markReceived'])->name('owner.purchases.receive');

        Route::get('/owner/stock-adjustments', [StockAdjustmentRequestController::class, 'ownerIndex'])->name('owner.adjustments.index');
        Route::post('/owner/stock-adjustments/{adjustmentRequest}/approve', [StockAdjustmentRequestController::class, 'approve'])->name('owner.adjustments.approve');
        Route::post('/owner/stock-adjustments/{adjustmentRequest}/reject', [StockAdjustmentRequestController::class, 'reject'])->name('owner.adjustments.reject');

        Route::get('/owner/supplier-accounts', [OwnerSupplierAccountController::class, 'index'])->name('owner.supplier-accounts.index');
        Route::post('/owner/supplier-accounts', [OwnerSupplierAccountController::class, 'store'])->name('owner.supplier-accounts.store');
        Route::patch('/owner/supplier-accounts/{supplierAccount}/status', [OwnerSupplierAccountController::class, 'updateStatus'])->name('owner.supplier-accounts.status');
        Route::put('/owner/supplier-accounts/{supplierAccount}/password', [OwnerSupplierAccountController::class, 'resetPassword'])->name('owner.supplier-accounts.password');

        Route::get('/owner/access-requests', [BusinessAccessRequestController::class, 'index'])->name('owner.access.index');
        Route::post('/owner/access-requests/{accessRequest}/approve', [BusinessAccessRequestController::class, 'approve'])->name('owner.access.approve');
        Route::post('/owner/access-requests/{accessRequest}/reject', [BusinessAccessRequestController::class, 'reject'])->name('owner.access.reject');
        Route::post('/owner/access-requests/{accessRequest}/revoke', [BusinessAccessRequestController::class, 'revoke'])->name('owner.access.revoke');

        Route::get('/owner/transactions', [TransactionController::class, 'index'])->name('owner.transactions.index');
        Route::get('/owner/transactions/stock-in', [TransactionController::class, 'stockInForm'])->name('owner.transactions.stockIn');
        Route::post('/owner/transactions/stock-in', [TransactionController::class, 'stockIn'])->name('owner.transactions.stockInStore');
        Route::get('/owner/transactions/stock-out', [TransactionController::class, 'stockOutForm'])->name('owner.transactions.stockOut');
        Route::post('/owner/transactions/stock-out', [TransactionController::class, 'stockOut'])->name('owner.transactions.stockOutStore');
        Route::get('/owner/transactions/adjustment', [TransactionController::class, 'adjustmentForm'])->name('owner.transactions.adjustment');
        Route::post('/owner/transactions/adjustment', [TransactionController::class, 'adjustment'])->name('owner.transactions.adjustmentStore');
        Route::get('/owner/transactions/spoilage', [TransactionController::class, 'spoilageForm'])->name('owner.transactions.spoilage');
        Route::post('/owner/transactions/spoilage', [TransactionController::class, 'spoilage'])->name('owner.transactions.spoilageStore');

        Route::get('/owner/audit-logs', [AuditLogController::class, 'index'])->name('owner.audit.index');
        Route::get('/owner/staff', [StaffController::class, 'index'])->name('owner.staff.index');
        Route::get('/owner/staff/create', [StaffController::class, 'create'])->name('owner.staff.create');
        Route::post('/owner/staff', [StaffController::class, 'store'])->name('owner.staff.store');
        Route::get('/owner/staff/{staff}/edit', [StaffController::class, 'edit'])->name('owner.staff.edit');
        Route::put('/owner/staff/{staff}', [StaffController::class, 'update'])->name('owner.staff.update');
        Route::patch('/owner/staff/{staff}/approve', [StaffController::class, 'approveRequest'])->name('owner.staff.approve');
        Route::delete('/owner/staff/{staff}/reject', [StaffController::class, 'rejectRequest'])->name('owner.staff.reject');
        Route::patch('/owner/staff/{staff}/status', [StaffController::class, 'updateStatus'])->name('owner.staff.status');
        Route::put('/owner/staff/{staff}/password', [StaffController::class, 'resetPassword'])->name('owner.staff.password');
        Route::get('/owner/settings', [SettingsController::class, 'index'])->name('owner.settings.index');
        Route::put('/owner/settings', [SettingsController::class, 'update'])->name('owner.settings.update');
        Route::post('/owner/settings/mobile-apk', [AppDownloadController::class, 'uploadApk'])->name('owner.settings.apk.upload');
        Route::post('/owner/settings/security/mfa-challenge', [SettingsController::class, 'sendMfaChallenge'])->name('owner.settings.mfa.challenge');
        Route::post('/owner/settings/security/mfa-authenticator', [SettingsController::class, 'configureAuthenticator'])->name('owner.settings.mfa.configure');
        Route::delete('/owner/settings/security/mfa-authenticator', [SettingsController::class, 'disableAuthenticator'])->name('owner.settings.mfa.disable');
        Route::post('/owner/settings/security/sessions/{user}/revoke', [SettingsController::class, 'revokeActiveSession'])->name('owner.settings.sessions.revoke');
        Route::post('/owner/settings/staff-access/verify', [SettingsController::class, 'verifyStaffAccessManagement'])->name('owner.settings.staff-access.verify');
        Route::post('/owner/settings/staff-access/lock', [SettingsController::class, 'lockStaffAccessManagement'])->name('owner.settings.staff-access.lock');
        Route::post('/owner/settings/staff-access/create', [SettingsController::class, 'storeStaffAccount'])->name('owner.settings.staff-access.create');
        Route::post('/owner/settings/inventory/categories', [InventorySettingsController::class, 'storeCategory'])->name('owner.settings.categories.store');
        Route::put('/owner/settings/inventory/categories/{category}', [InventorySettingsController::class, 'updateCategory'])->name('owner.settings.categories.update');
        Route::patch('/owner/settings/inventory/categories/{category}/status', [InventorySettingsController::class, 'updateCategoryStatus'])->name('owner.settings.categories.status');
        Route::post('/owner/settings/inventory/units', [InventorySettingsController::class, 'storeUnit'])->name('owner.settings.units.store');
        Route::put('/owner/settings/inventory/units/{unit}', [InventorySettingsController::class, 'updateUnit'])->name('owner.settings.units.update');
        Route::patch('/owner/settings/inventory/units/{unit}/status', [InventorySettingsController::class, 'updateUnitStatus'])->name('owner.settings.units.status');
        Route::get('/owner/reports', [ReportController::class, 'index'])->name('owner.reports.index');
        Route::get('/owner/reports/export', [ReportController::class, 'exportCsv'])->name('owner.reports.export');
        Route::get('/owner/backup', [BackupController::class, 'index'])->name('owner.backup.index');
        Route::post('/owner/backup/download', [BackupController::class, 'downloadBackup'])->name('owner.backup.download');
    });

    Route::middleware('role:staff')->group(function () {
        Route::get('/staff/dashboard', [DashboardController::class, 'staffDashboard'])->name('staff.dashboard');

        Route::get('/staff/inventory', [InventoryItemController::class, 'index'])->name('staff.inventory.index');
        Route::get('/staff/transactions', [TransactionController::class, 'index'])->name('staff.transactions.index');
        Route::get('/staff/transactions/stock-in', [TransactionController::class, 'stockInForm'])->name('staff.transactions.stockIn');
        Route::post('/staff/transactions/stock-in', [TransactionController::class, 'stockIn'])->name('staff.transactions.stockInStore');
        Route::get('/staff/transactions/stock-out', [TransactionController::class, 'stockOutForm'])->name('staff.transactions.stockOut');
        Route::post('/staff/transactions/stock-out', [TransactionController::class, 'stockOut'])->name('staff.transactions.stockOutStore');
        Route::get('/staff/transactions/adjustment', [TransactionController::class, 'adjustmentForm'])->name('staff.transactions.adjustment');
        Route::post('/staff/transactions/adjustment', [TransactionController::class, 'adjustment'])->name('staff.transactions.adjustmentStore');
        Route::get('/staff/transactions/spoilage', [TransactionController::class, 'spoilageForm'])->name('staff.transactions.spoilage');
        Route::post('/staff/transactions/spoilage', [TransactionController::class, 'spoilage'])->name('staff.transactions.spoilageStore');
        Route::get('/staff/stock-adjustments', [StockAdjustmentRequestController::class, 'staffIndex'])->name('staff.adjustments.index');
        Route::post('/staff/stock-adjustments', [StockAdjustmentRequestController::class, 'staffStore'])->name('staff.adjustments.store');
    });

    Route::middleware('role:system_admin')->prefix('admin')->name('sysadmin.')->group(function () {
        Route::get('/dashboard', [SystemAdminController::class, 'dashboard'])->name('dashboard');
        Route::get('/health', [SystemAdminController::class, 'health'])->name('health');
        Route::get('/security', [SystemAdminController::class, 'security'])->name('security');
        Route::put('/security/config', [SystemAdminController::class, 'updateSecurityConfig'])->name('security.update');
        Route::post('/security/sessions/{user}/revoke', [SystemAdminController::class, 'revokeAdminSession'])->name('security.sessions.revoke');
        Route::get('/backup', [BackupController::class, 'index'])->name('backup');
        Route::post('/backup/download', [BackupController::class, 'downloadBackup'])->name('backup.download');
        Route::get('/config', [SystemAdminController::class, 'config'])->name('config');
        Route::put('/config', [SystemAdminController::class, 'updateConfig'])->name('config.update');
        Route::get('/accounts', [SystemAdminController::class, 'accounts'])->name('accounts.index');
        Route::post('/accounts', [SystemAdminController::class, 'storeAccount'])->name('accounts.store');
        Route::patch('/accounts/{account}/status', [SystemAdminController::class, 'updateAccountStatus'])->name('accounts.status');
        Route::get('/access-requests', [SystemAdminController::class, 'accessRequests'])->name('access.index');
        Route::post('/access-requests', [SystemAdminController::class, 'storeAccessRequest'])->name('access.store');
        Route::get('/access-requests/{accessRequest}/view', [SystemAdminController::class, 'viewGrantedBusinessData'])->name('access.view');
    });

    Route::middleware('permission:technical_administration')->group(function () {
        Route::put('/technical-config', [SystemAdminController::class, 'updateConfig'])->name('technical.config.update');
    });

    Route::middleware('role:supplier')->prefix('supplier')->name('supplier.')->group(function () {
        Route::get('/dashboard', [SupplierPortalController::class, 'dashboard'])->name('dashboard');
        Route::get('/orders', [SupplierPortalController::class, 'orders'])->name('orders.index');
        Route::post('/orders/{purchase}/confirm', [SupplierPortalController::class, 'confirm'])->name('orders.confirm');
        Route::post('/orders/{purchase}/reject', [SupplierPortalController::class, 'reject'])->name('orders.reject');
        Route::patch('/orders/{purchase}/delivery', [SupplierPortalController::class, 'updateDelivery'])->name('orders.delivery');
        Route::get('/profile', [SupplierPortalController::class, 'profile'])->name('profile');
        Route::put('/profile', [SupplierPortalController::class, 'updateProfile'])->name('profile.update');
    });
});
