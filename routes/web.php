<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventoryItemController;
use App\Http\Controllers\InventorySettingsController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index'])->name('home');
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
        Route::patch('/owner/staff/{staff}/status', [StaffController::class, 'updateStatus'])->name('owner.staff.status');
        Route::put('/owner/staff/{staff}/password', [StaffController::class, 'resetPassword'])->name('owner.staff.password');
        Route::get('/owner/settings', [SettingsController::class, 'index'])->name('owner.settings.index');
        Route::put('/owner/settings', [SettingsController::class, 'update'])->name('owner.settings.update');
        Route::post('/owner/settings/inventory/categories', [InventorySettingsController::class, 'storeCategory'])->name('owner.settings.categories.store');
        Route::put('/owner/settings/inventory/categories/{category}', [InventorySettingsController::class, 'updateCategory'])->name('owner.settings.categories.update');
        Route::patch('/owner/settings/inventory/categories/{category}/status', [InventorySettingsController::class, 'updateCategoryStatus'])->name('owner.settings.categories.status');
        Route::post('/owner/settings/inventory/units', [InventorySettingsController::class, 'storeUnit'])->name('owner.settings.units.store');
        Route::put('/owner/settings/inventory/units/{unit}', [InventorySettingsController::class, 'updateUnit'])->name('owner.settings.units.update');
        Route::patch('/owner/settings/inventory/units/{unit}/status', [InventorySettingsController::class, 'updateUnitStatus'])->name('owner.settings.units.status');
        Route::get('/owner/reports', [ReportController::class, 'index'])->name('owner.reports.index');
        Route::get('/owner/reports/export', [ReportController::class, 'exportCsv'])->name('owner.reports.export');
        Route::get('/owner/backup', [BackupController::class, 'index'])->name('owner.backup.index');
        Route::get('/owner/backup/download', [BackupController::class, 'downloadBackup'])->name('owner.backup.download');
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
    });
});
