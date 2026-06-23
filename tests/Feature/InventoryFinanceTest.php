<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Product;
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
