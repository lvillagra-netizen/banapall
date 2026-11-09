<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BinController;
use App\Http\Controllers\CaliberController;
use App\Http\Controllers\ChamberController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', LandingController::class)->name('landing');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/admin', fn () => redirect()->route('admin.dashboard'))->name('admin.home');
    Route::get('/dashboard', DashboardController::class)->middleware('role:admin')->name('admin.dashboard');

    Route::get('/pos', [PosController::class, 'index'])->middleware('permission:pos.access')->name('pos.index');
    Route::post('/pos', [PosController::class, 'store'])->middleware('permission:sales.create')->name('pos.store');
    Route::get('/pos/{sale}/edit', [PosController::class, 'edit'])->middleware('permission:sales.create')->name('pos.edit');
    Route::put('/pos/{sale}', [PosController::class, 'update'])->middleware('permission:sales.create')->name('pos.update');

    Route::get('/sales', [SaleController::class, 'index'])->middleware('permission:sales.view')->name('sales.index');
    Route::get('/sales/{sale}', [SaleController::class, 'show'])->middleware('permission:sales.view')->name('sales.show');
    Route::post('/sales/{sale}/validate', [SaleController::class, 'validateSale'])->middleware('permission:sales.validate')->name('sales.validate');
    Route::post('/sales/{sale}/payments', [SaleController::class, 'pay'])->middleware('permission:sales.payment')->name('sales.payments.store');
    Route::post('/sales/{sale}/cancel', [SaleController::class, 'cancel'])->middleware('permission:sales.cancel')->name('sales.cancel');

    Route::middleware('permission:customers.manage')->group(function () {
        Route::get('/customers/create', [CustomerController::class, 'create'])->name('customers.create');
        Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store');
    });

    Route::middleware('permission:customers.view')->group(function () {
        Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
        Route::get('/customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
    });

    Route::middleware('permission:customers.manage')->group(function () {
        Route::get('/customers/{customer}/edit', [CustomerController::class, 'edit'])->name('customers.edit');
        Route::put('/customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
        Route::delete('/customers/{customer}', [CustomerController::class, 'destroy'])->name('customers.destroy');
    });

    Route::middleware('permission:products.manage')->group(function () {
        Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
        Route::post('/products', [ProductController::class, 'store'])->name('products.store');
    });

    Route::middleware('permission:products.view')->group(function () {
        Route::get('/products', [ProductController::class, 'index'])->name('products.index');
        Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
    });

    Route::middleware('permission:products.manage')->group(function () {
        Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
        Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
        Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
        Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');
        Route::get('/calibers', [CaliberController::class, 'index'])->name('calibers.index');
        Route::post('/calibers', [CaliberController::class, 'store'])->name('calibers.store');
        Route::put('/calibers/{caliber}', [CaliberController::class, 'update'])->name('calibers.update');
        Route::delete('/calibers/{caliber}', [CaliberController::class, 'destroy'])->name('calibers.destroy');
    });

    Route::middleware('permission:inventory.manage')->group(function () {
        Route::get('/warehouses', [WarehouseController::class, 'index'])->name('warehouses.index');
        Route::post('/warehouses', [WarehouseController::class, 'store'])->name('warehouses.store');
        Route::put('/warehouses/{warehouse}', [WarehouseController::class, 'update'])->name('warehouses.update');
        Route::delete('/warehouses/{warehouse}', [WarehouseController::class, 'destroy'])->name('warehouses.destroy');

        Route::get('/chambers', [ChamberController::class, 'index'])->name('chambers.index');
        Route::post('/chambers', [ChamberController::class, 'store'])->name('chambers.store');
        Route::put('/chambers/{chamber}', [ChamberController::class, 'update'])->name('chambers.update');
        Route::delete('/chambers/{chamber}', [ChamberController::class, 'destroy'])->name('chambers.destroy');

        Route::get('/bins', [BinController::class, 'index'])->name('bins.index');
        Route::post('/bins', [BinController::class, 'store'])->name('bins.store');
        Route::put('/bins/{bin}', [BinController::class, 'update'])->name('bins.update');
        Route::delete('/bins/{bin}', [BinController::class, 'destroy'])->name('bins.destroy');
    });

    Route::get('/products/{product}', [ProductController::class, 'show'])
        ->middleware('permission:products.view')
        ->name('products.show');

    Route::middleware('permission:inventory.view')->group(function () {
        Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
        Route::get('/inventory/movements', [InventoryController::class, 'movements'])->name('inventory.movements');
    });

    Route::middleware('permission:inventory.manage')->group(function () {
        Route::post('/inventory/adjust', [InventoryController::class, 'adjust'])->name('inventory.adjust');
        Route::post('/inventory/receive', [InventoryController::class, 'receive'])->name('inventory.receive');
        Route::post('/inventory/transfer', [InventoryController::class, 'transfer'])->name('inventory.transfer');
        Route::post('/inventory/movements/{movement}/approve', [InventoryController::class, 'approve'])->name('inventory.movements.approve');
    });

    Route::middleware('permission:billing.view')->group(function () {
        Route::get('/billing/documents', [BillingController::class, 'documents'])->name('billing.documents');
        Route::get('/billing/documents/{document}/pdf', [BillingController::class, 'download'])->name('billing.documents.pdf');
    });

    Route::middleware('permission:folios.manage')->group(function () {
        Route::get('/billing/folios', [BillingController::class, 'folios'])->name('billing.folios');
        Route::post('/billing/folios', [BillingController::class, 'storeFolio'])->name('billing.folios.store');
        Route::put('/billing/folios/{range}', [BillingController::class, 'updateFolio'])->name('billing.folios.update');
    });

    Route::get('/billing/accounting', [BillingController::class, 'accounting'])
        ->middleware('permission:accounting.view')
        ->name('billing.accounting');

    Route::middleware('permission:reports.view')->group(function () {
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');
    });

    Route::middleware('permission:users.manage')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });

    Route::middleware('permission:settings.manage')->group(function () {
        Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
        Route::post('/settings/rates', [SettingsController::class, 'storeRate'])->name('settings.rates.store');
    });

    Route::get('/audit', [AuditLogController::class, 'index'])
        ->middleware('permission:audit.view')
        ->name('audit.index');
});
