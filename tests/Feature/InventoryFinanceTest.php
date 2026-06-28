<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Expense;
use App\Models\Order;
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

test('dashboard renders when monthly sales are zero', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Monthly Sales Calendar');
});

test('account payments count toward paid amount and remaining balance', function () {
    $product = Product::factory()->create([
        'name' => 'Arabica Coffee Beans',
        'price' => 18.50,
        'purchase_price' => 10,
        'quantity' => 10,
    ]);
    $order = Order::factory()->create(['user_id' => $this->user->id]);
    $order->items()->create([
        'product_id' => $product->id,
        'price' => 18.50,
        'discount' => 5.00,
        'quantity' => 1,
        'unit_cost' => 10,
    ]);
    $order->payments()->create([
        'amount' => 10.00,
        'method' => 'cash',
        'user_id' => $this->user->id,
    ]);
    $order->payments()->create([
        'amount' => 3.00,
        'method' => 'account',
        'user_id' => $this->user->id,
    ]);

    $order->load(['items', 'payments']);

    expect($order->total())->toBe(13.5)
        ->and($order->receivedAmount())->toBe(13.0)
        ->and($order->remainingBalance())->toBe(0.5);

    $this->get(route('orders.index'))
        ->assertOk()
        ->assertSee('13.00')
        ->assertSee('0.50')
        ->assertSee('data-payments=', false);

    $this->get(route('orders.show', $order))
        ->assertOk()
        ->assertSee('Payment Methods')
        ->assertSee('Cash')
        ->assertSee('Account')
        ->assertSee('Card')
        ->assertSee('Used')
        ->assertSee('Not used')
        ->assertSee('10.00')
        ->assertSee('3.00')
        ->assertSee('0.00');
});

test('business and product analytics use the same net sales and gross profit formula', function () {
    $product = Product::factory()->create([
        'name' => 'Profit Check Pack',
        'price' => 400,
        'purchase_price' => 300,
        'quantity' => 10,
        'track_stock' => true,
    ]);
    $order = Order::factory()->create([
        'user_id' => $this->user->id,
        'created_at' => now(),
    ]);
    $order->items()->create([
        'product_id' => $product->id,
        'price' => 800,
        'discount' => 50,
        'quantity' => 2,
        'unit_cost' => 300,
    ]);
    $order->payments()->create([
        'amount' => 750,
        'method' => 'cash',
        'user_id' => $this->user->id,
    ]);
    Expense::create([
        'user_id' => $this->user->id,
        'expense_date' => now()->toDateString(),
        'category' => 'Rent',
        'amount' => 100,
        'description' => 'Daily shop expense',
    ]);

    $business = $this->get(route('reports.business', [
        'date_from' => now()->toDateString(),
        'date_to' => now()->toDateString(),
    ]));
    $analytics = $this->get(route('reports.product-analytics', [
        'date_from' => now()->toDateString(),
        'date_to' => now()->toDateString(),
    ]));

    $business->assertViewHas('grossSales', 800.0)
        ->assertViewHas('discounts', 50.0)
        ->assertViewHas('sales', 750.0)
        ->assertViewHas('cost', 600.0)
        ->assertViewHas('grossProfit', 150.0)
        ->assertViewHas('netProfit', 50.0);

    $analytics->assertViewHas('summary', fn (array $summary): bool =>
        (float) $summary['total_revenue'] === 750.0
        && (float) $summary['total_cost'] === 600.0
        && (float) $summary['total_profit'] === 150.0
    );
});

test('roles allow manager management while salesman can only access working sections', function () {
    $salesman = User::factory()->salesman()->create();
    $manager = User::factory()->manager()->create();

    $this->actingAs($salesman)
        ->get(route('settings.index'))
        ->assertForbidden();

    $this->actingAs($salesman)
        ->get(route('purchases.index'))
        ->assertForbidden();

    $this->actingAs($salesman)
        ->get(route('reports.business'))
        ->assertForbidden();

    $this->actingAs($salesman)
        ->get(route('cart.index'))
        ->assertOk();

    $this->actingAs($salesman)
        ->get(route('customers.index'))
        ->assertOk();

    $this->actingAs($salesman)
        ->get(route('stock-adjustments.create'))
        ->assertOk()
        ->assertSee('Stock out')
        ->assertSee('Customer return')
        ->assertDontSee('Stock in');

    $this->actingAs($salesman)
        ->get(route('expenses.index'))
        ->assertOk();

    $this->actingAs($manager)
        ->get(route('settings.index'))
        ->assertOk();
});
