<?php

use App\Http\Controllers\Admin\HomeController;
use App\Http\Controllers\Finance\ExpenseController;
use App\Http\Controllers\Inventory\CategoryController;
use App\Http\Controllers\Inventory\ProductController;
use App\Http\Controllers\Inventory\PurchaseCartController;
use App\Http\Controllers\Inventory\PurchaseController;
use App\Http\Controllers\Inventory\StockAdjustmentController;
use App\Http\Controllers\Management\CustomerController;
use App\Http\Controllers\Management\SupplierController;
use App\Http\Controllers\Pos\CartController;
use App\Http\Controllers\Pos\OrderController;
use App\Http\Controllers\Reports\BusinessReportController;
use App\Http\Controllers\Reports\AuditLogController;
use App\Http\Controllers\Reports\DataExportController;
use App\Http\Controllers\Reports\ProductAnalyticsController;
use App\Http\Controllers\Settings\SettingController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', fn(): Redirector|RedirectResponse => redirect('/admin'));

Auth::routes();

Route::prefix('admin')->middleware(['auth', 'locale'])->group(function (): void {
    Route::get('/', HomeController::class)->name('home');
    Route::middleware('role:admin,manager')->group(function (): void {
        Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
        Route::post('/settings', [SettingController::class, 'store'])->name('settings.store');
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
        Route::get('/exports', [DataExportController::class, 'index'])->name('exports.index');
        Route::get('/exports/{type}', [DataExportController::class, 'export'])->name('exports.download');
    });
    Route::resource('categories', CategoryController::class)->except(['show']);
    Route::resource('products', ProductController::class);
    Route::resource('stock-adjustments', StockAdjustmentController::class)->only(['index', 'create', 'store']);
    Route::resource('expenses', ExpenseController::class)->only(['index', 'create', 'store', 'destroy']);
    Route::get('/reports/business', [BusinessReportController::class, 'index'])->name('reports.business');
    Route::get('/reports/product-analytics', [ProductAnalyticsController::class, 'index'])->name('reports.product-analytics');
    Route::get('/reports/product-analytics/data', [ProductAnalyticsController::class, 'data'])->name('reports.product-analytics.data');
    Route::resource('customers', CustomerController::class);
    Route::get('/orders/{order}/return', [OrderController::class, 'returnForm'])->name('orders.return');
    Route::post('/orders/{order}/return', [OrderController::class, 'processReturn'])->name('orders.return.store');
    Route::resource('orders', OrderController::class);
    Route::resource('suppliers', SupplierController::class);

    // POS Cart
    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart', [CartController::class, 'store'])->name('cart.store');
    Route::post('/cart/change-qty', [CartController::class, 'changeQty']);
    Route::delete('/cart/delete', [CartController::class, 'delete']);
    Route::delete('/cart/empty', [CartController::class, 'empty']);

    Route::get('/purchases/data', [PurchaseController::class, 'data'])->name('purchases.data');
    Route::get('/purchases/{purchase}/receipt', [PurchaseController::class, 'receipt'])->name('purchases.receipt');
    Route::redirect('/purchase', '/admin/purchases/create')->name('purchase.index');
    Route::resource('purchases', PurchaseController::class);

    // Purchase Cart API
    Route::prefix('purchase-cart')->name('purchase-cart.')->group(function (): void {
        Route::get('/', [PurchaseCartController::class, 'index'])->name('index');
        Route::post('/', [PurchaseCartController::class, 'store'])->name('store');
        Route::post('/change-qty', [PurchaseCartController::class, 'changeQty'])->name('change-qty');
        Route::post('/change-price', [PurchaseCartController::class, 'changePrice'])->name('change-price');
        Route::delete('/delete', [PurchaseCartController::class, 'delete'])->name('delete');
        Route::delete('/empty', [PurchaseCartController::class, 'empty'])->name('empty');
    });

    // Orders
    Route::post('/orders/partial-payment', [OrderController::class, 'partialPayment'])->name('orders.partial-payment');

    // Translations
    Route::get('/locale/{type}', function ($type) {
        $translations = trans($type);
        return response()->json($translations);
    });

    // Language Switch
    Route::get('/lang-switch/{lang}', function ($lang) {
        $supportedLocales = ['en', 'es'];

        if (in_array($lang, $supportedLocales)) {
            session(['locale' => $lang]);
            app()->setLocale($lang);
        }

        return redirect()->back();
    })->name('lang.switch');
});
