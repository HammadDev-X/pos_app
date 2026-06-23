<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\StockAdjustment;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('damage and expired stock-out entries reduce inventory', function () {
    $product = Product::factory()->create(['quantity' => 20]);

    $this->post(route('stock-adjustments.store'), [
        'product_id' => $product->id,
        'type' => 'damage',
        'quantity' => 3,
        'reason' => 'Damaged during loading',
    ])->assertRedirect(route('stock-adjustments.index'))
        ->assertSessionHas('success');

    expect((float) $product->fresh()->quantity)->toBe(17.0);

    $this->post(route('stock-adjustments.store'), [
        'product_id' => $product->id,
        'type' => 'expired_disposal',
        'quantity' => 2,
        'reason' => 'Expired batch disposed',
    ])->assertRedirect(route('stock-adjustments.index'))
        ->assertSessionHas('success');

    expect((float) $product->fresh()->quantity)->toBe(15.0);

    expect(StockAdjustment::where('type', 'damage')->exists())->toBeTrue()
        ->and(StockAdjustment::where('type', 'expired_disposal')->exists())->toBeTrue();
});

test('stock in out return and disposed movements update product inventory', function () {
    $product = Product::factory()->create(['quantity' => 10]);

    $this->post(route('stock-adjustments.store'), [
        'product_id' => $product->id,
        'type' => 'stock_in',
        'quantity' => 5,
        'reason' => 'New stock received',
    ])->assertRedirect(route('stock-adjustments.index'));

    expect((float) $product->fresh()->quantity)->toBe(15.0);

    $this->post(route('stock-adjustments.store'), [
        'product_id' => $product->id,
        'type' => 'stock_out',
        'quantity' => 4,
        'reason' => 'Stock issued',
    ])->assertRedirect(route('stock-adjustments.index'));

    expect((float) $product->fresh()->quantity)->toBe(11.0);

    $this->post(route('stock-adjustments.store'), [
        'product_id' => $product->id,
        'type' => 'customer_return',
        'quantity' => 2,
        'reason' => 'Customer returned sealed item',
    ])->assertRedirect(route('stock-adjustments.index'));

    expect((float) $product->fresh()->quantity)->toBe(13.0);

    $this->post(route('stock-adjustments.store'), [
        'product_id' => $product->id,
        'type' => 'disposed',
        'quantity' => 3,
        'reason' => 'Disposed damaged stock',
    ])->assertRedirect(route('stock-adjustments.index'));

    expect((float) $product->fresh()->quantity)->toBe(10.0)
        ->and(StockAdjustment::where('type', 'stock_in')->exists())->toBeTrue()
        ->and(StockAdjustment::where('type', 'stock_out')->exists())->toBeTrue()
        ->and(StockAdjustment::where('type', 'customer_return')->exists())->toBeTrue()
        ->and(StockAdjustment::where('type', 'disposed')->exists())->toBeTrue();
});

test('business report includes inventory valuation by product and category', function () {
    $frozen = Category::create(['name' => 'Frozen', 'status' => true]);
    $dry = Category::create(['name' => 'Dry Goods', 'status' => true]);

    Product::factory()->create([
        'name' => 'Chicken Pack',
        'category_id' => $frozen->id,
        'quantity' => 10,
        'purchase_price' => 100,
        'price' => 130,
        'track_stock' => true,
    ]);
    Product::factory()->create([
        'name' => 'Rice Bag',
        'category_id' => $dry->id,
        'quantity' => 5,
        'purchase_price' => 200,
        'price' => 250,
        'track_stock' => true,
    ]);

    $response = $this->get(route('reports.business'));

    $response->assertOk()
        ->assertViewHas('inventoryQuantity', 15.0)
        ->assertViewHas('inventoryCostValue', 2000.0)
        ->assertViewHas('inventorySellingValue', 2550.0)
        ->assertViewHas('inventoryEstimatedMargin', 550.0)
        ->assertViewHas('productStockValues', fn ($rows) => $rows->pluck('name')->contains('Chicken Pack'))
        ->assertViewHas('categoryStockValues', fn ($rows) => $rows->pluck('category')->contains('Frozen'));
});

test('expense screens expose required operating expense categories', function () {
    $required = [
        'Petrol / Fuel',
        'Packaging',
        'Delivery',
        'Salary',
        'Rent',
        'Repairs & Maintenance',
        'Marketing',
        'Other Expenses',
    ];

    $response = $this->get(route('expenses.create'));

    foreach ($required as $category) {
        $response->assertSee($category);
    }
});

test('business reports expose daily weekly monthly and inventory report sections', function () {
    $available = Product::factory()->create([
        'name' => 'Available Fish',
        'quantity' => 25,
        'minimum_stock_level' => 5,
        'track_stock' => true,
    ]);
    Product::factory()->create([
        'name' => 'Low Stock Nuggets',
        'quantity' => 3,
        'minimum_stock_level' => 5,
        'track_stock' => true,
    ]);
    Product::factory()->create([
        'name' => 'Out Stock Fries',
        'quantity' => 0,
        'track_stock' => true,
    ]);
    $purchase = Purchase::factory()->completed()->create(['user_id' => $this->user->id]);
    PurchaseItem::factory()->create([
        'purchase_id' => $purchase->id,
        'product_id' => $available->id,
        'quantity' => 10,
        'expiry_date' => now()->addDays(10)->toDateString(),
    ]);

    $response = $this->get(route('reports.business'));

    $response->assertOk()
        ->assertSee('Daily Report')
        ->assertSee('Weekly Report')
        ->assertSee('Monthly Report')
        ->assertSee('Inventory Report')
        ->assertSee('Available Stock')
        ->assertSee('Low Stock Items')
        ->assertSee('Out-of-stock Items')
        ->assertSee('Expiring Soon Items')
        ->assertViewHas('dailyReport')
        ->assertViewHas('weeklyReport')
        ->assertViewHas('monthlyReport')
        ->assertViewHas('availableStockItems', fn ($items) => $items->pluck('name')->contains('Available Fish'))
        ->assertViewHas('lowStockItems', fn ($items) => $items->pluck('name')->contains('Low Stock Nuggets'))
        ->assertViewHas('outOfStockItems', fn ($items) => $items->pluck('name')->contains('Out Stock Fries'))
        ->assertViewHas('expiringSoonItems', fn ($items) => $items->pluck('product.name')->contains('Available Fish'));
});

test('roles allow admin management while blocking cashier from sensitive sections', function () {
    $cashier = User::factory()->cashier()->create();

    $this->actingAs($cashier)
        ->get(route('settings.index'))
        ->assertForbidden();

    $this->actingAs($cashier)
        ->get(route('cart.index'))
        ->assertOk();

    $this->actingAs($this->user)
        ->get(route('settings.index'))
        ->assertOk();
});
