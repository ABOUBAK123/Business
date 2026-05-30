<?php

use App\Http\Controllers\ArticleController;
use App\Http\Controllers\StockManagementController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CommissionerController;
use App\Http\Controllers\CommissionerRegistrationController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\TenantRegistrationController;
use App\Http\Controllers\ArticleImportController;
use App\Http\Controllers\CashClosingController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\SaleReturnController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\SuperAdmin\CommissionerManagementController;
use App\Http\Controllers\SuperAdmin\SettingController;
use App\Http\Controllers\SuperAdmin\TenantController as SuperAdminTenantController;
use App\Http\Controllers\SuperAdmin\PlanController;
use Illuminate\Support\Facades\Route;

// Public landing
Route::get('/', fn() => redirect()->route('register.plans'));
Route::get('/plans', [TenantRegistrationController::class, 'showPlans'])->name('register.plans');
Route::get('/register/shop', [TenantRegistrationController::class, 'showForm'])->name('register.form');
Route::post('/register/shop', [TenantRegistrationController::class, 'register'])->name('register.shop');
Route::get('/register/commissioner', [CommissionerRegistrationController::class, 'create'])->name('register.commissioner');
Route::post('/register/commissioner', [CommissionerRegistrationController::class, 'store'])->name('register.commissioner.store');

// Auth (Breeze)
require __DIR__.'/auth.php';

// Subscription expired page
Route::get('/subscription/expired', fn() => view('subscription.expired'))->name('subscription.expired');

// ─── Super Admin routes ───────────────────────────────────────────────────────
Route::middleware(['auth', 'super.admin'])->prefix('super-admin')->name('super-admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::resource('tenants', SuperAdminTenantController::class)->only(['index', 'show']);
    Route::patch('tenants/{id}/toggle-status', [SuperAdminTenantController::class, 'toggleStatus'])->name('tenants.toggle-status');
    Route::patch('tenants/{id}/change-plan', [SuperAdminTenantController::class, 'changePlan'])->name('tenants.change-plan');
    Route::resource('plans', PlanController::class)->only(['index', 'create', 'store', 'edit', 'update']);
    Route::resource('commissioners', CommissionerManagementController::class)->only(['index', 'create', 'store', 'destroy']);
    Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('settings/{group}', [SettingController::class, 'update'])->name('settings.update');
});

// ─── Commissioner routes ──────────────────────────────────────────────────────
Route::middleware(['auth', 'commissionnaire'])->prefix('commissioner')->name('commissioner.')->group(function () {
    Route::get('/dashboard', [CommissionerController::class, 'dashboard'])->name('dashboard');
    Route::get('/shops', [CommissionerController::class, 'shops'])->name('shops');
    Route::get('/shops/create', [CommissionerController::class, 'createShop'])->name('shops.create');
    Route::post('/shops', [CommissionerController::class, 'storeShop'])->name('shops.store');
    Route::get('/commissions', [CommissionerController::class, 'commissions'])->name('commissions');
});

// ─── Tenant (boutique) routes ─────────────────────────────────────────────────
Route::middleware(['auth', 'tenant.active'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Categories & Suppliers (proprietaire)
    Route::post('categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::put('categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

    Route::post('suppliers', [SupplierController::class, 'store'])->name('suppliers.store');
    Route::put('suppliers/{supplier}', [SupplierController::class, 'update'])->name('suppliers.update');
    Route::delete('suppliers/{supplier}', [SupplierController::class, 'destroy'])->name('suppliers.destroy');

    // Stock management
    Route::get('stock', [StockManagementController::class, 'index'])->name('stock.index');
    Route::post('stock/{article}/replenish', [StockManagementController::class, 'replenish'])->name('stock.replenish');

    // Import articles CSV
    Route::get('articles/import', [ArticleImportController::class, 'showForm'])->name('articles.import');
    Route::get('articles/import/template', [ArticleImportController::class, 'downloadTemplate'])->name('articles.import.template');
    Route::post('articles/import/preview', [ArticleImportController::class, 'preview'])->name('articles.import.preview');
    Route::post('articles/import/store', [ArticleImportController::class, 'import'])->name('articles.import.store');

    // Articles & QR Codes
    Route::get('articles/labels', [ArticleController::class, 'labels'])->name('articles.labels');
    Route::resource('articles', ArticleController::class);
    Route::get('articles/{article}/qr', [ArticleController::class, 'printQr'])->name('articles.qr');
    Route::post('articles/bulk-qr', [ArticleController::class, 'bulkQr'])->name('articles.bulk-qr');
    Route::get('articles/generate-code', [ArticleController::class, 'generateCode'])->name('articles.generate-code');
    Route::post('articles/{article}/stock', [ArticleController::class, 'updateStock'])->name('articles.stock');

    // Inventaire physique
    Route::get('inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::get('inventory/new', [InventoryController::class, 'create'])->name('inventory.create');
    Route::post('inventory', [InventoryController::class, 'store'])->name('inventory.store');
    Route::get('inventory/{inventory}', [InventoryController::class, 'show'])->name('inventory.show');
    Route::post('inventory/{inventory}/lines', [InventoryController::class, 'saveLine'])->name('inventory.save-lines');
    Route::post('inventory/{inventory}/finalize', [InventoryController::class, 'finalize'])->name('inventory.finalize');

    // Retours & avoirs
    Route::get('returns', [SaleReturnController::class, 'index'])->name('returns.index');
    Route::get('returns/new', [SaleReturnController::class, 'create'])->name('returns.create');
    Route::post('returns', [SaleReturnController::class, 'store'])->name('returns.store');
    Route::get('returns/{return}', [SaleReturnController::class, 'show'])->name('returns.show');

    // Clôture de caisse
    Route::get('cash', [CashClosingController::class, 'index'])->name('cash.index');
    Route::get('cash/new', [CashClosingController::class, 'create'])->name('cash.create');
    Route::post('cash', [CashClosingController::class, 'store'])->name('cash.store');
    Route::get('cash/{cash}', [CashClosingController::class, 'show'])->name('cash.show');

    // Sales (POS)
    Route::get('sales', [SaleController::class, 'index'])->name('sales.index');
    Route::get('sales/new', [SaleController::class, 'create'])->name('sales.create');
    Route::post('sales', [SaleController::class, 'store'])->name('sales.store');
    Route::get('sales/{sale}', [SaleController::class, 'show'])->name('sales.show');
    Route::get('sales/{sale}/receipt', [SaleController::class, 'receipt'])->name('sales.receipt');
    Route::get('sales/{sale}/invoice', [SaleController::class, 'invoice'])->name('sales.invoice');
    Route::get('api/articles/search', [SaleController::class, 'searchArticle'])->name('articles.search');
    Route::post('api/sales/scan-qr', [SaleController::class, 'scanQr'])->name('sales.scan-qr');

    // Branches
    Route::resource('branches', BranchController::class)->only(['index', 'create', 'store', 'edit', 'update']);

    // Customers
    Route::resource('customers', CustomerController::class);
    Route::post('customers/{customer}/payment', [CustomerController::class, 'recordPayment'])->name('customers.payment');

    // Users management
    Route::resource('users', UserManagementController::class)->only(['index', 'create', 'store', 'edit', 'update']);
    Route::patch('users/{user}/toggle-active', [UserManagementController::class, 'toggleActive'])->name('users.toggle-active');

    // Reports
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('sales', [ReportController::class, 'sales'])->name('sales');
        Route::get('stock', [ReportController::class, 'stock'])->name('stock');
        Route::get('top-articles', [ReportController::class, 'topArticles'])->name('top-articles');
        Route::get('financial', [ReportController::class, 'financial'])->name('financial');
    });
});
