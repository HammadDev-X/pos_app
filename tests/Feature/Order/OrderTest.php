<?php

declare(strict_types=1);

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

describe('Order Index', function () {
    test('authenticated users can view orders index', function () {
        $response = $this->get(route('orders.index'));

        $response->assertOk()
            ->assertViewIs('orders.index')
            ->assertViewHas(['orders', 'total', 'receivedAmount']);
    });

    test('guests cannot view orders index', function () {
        auth()->logout();

        $this->get(route('orders.index'))
            ->assertRedirect(route('login'));
    });

    test('orders are paginated and ordered by latest', function () {
        Order::factory()->count(15)->create();

        $response = $this->get(route('orders.index'));

        $response->assertViewHas('orders', function ($orders) {
            return $orders->count() === 10;
        });
    });

    test('orders can be filtered by date range', function () {
        $oldOrder = Order::factory()->create(['created_at' => now()->subDays(20)]);
        $newOrder = Order::factory()->create(['created_at' => now()->subDays(10)]);

        $response = $this->get(route('orders.index', [
            'start_date' => now()->subDays(15)->format('Y-m-d'),
            'end_date' => now()->subDays(5)->format('Y-m-d'),
        ]));

        $response->assertViewHas('orders', function ($orders) use ($oldOrder, $newOrder) {
            return !$orders->contains($oldOrder) && $orders->contains($newOrder);
        });
    });

    test('total and receivedAmount are calculated correctly', function () {
        $order = Order::factory()->create();
        $order->items()->create([
            'price' => 100,
            'quantity' => 2,
            'product_id' => Product::factory()->create()->id,
        ]);
        $order->payments()->create(['amount' => 75, 'user_id' => $this->user->id]);

        $response = $this->get(route('orders.index'));

        $response->assertViewHas('total', 100)
            ->assertViewHas('receivedAmount', 75);
    });
});

describe('Order Store', function () {
    test('authenticated users can create order from cart', function () {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['price' => 100, 'quantity' => 10]);

        $this->user->cart()->attach($product->id, ['quantity' => 2]);

        $this->postJson(route('orders.store'), [
            'customer_id' => $customer->id,
            'amount' => 200,
        ]);

        $this->assertDatabaseHas('orders', [
            'customer_id' => $customer->id,
            'user_id' => $this->user->id,
        ]);
    });

    test('order creates items and payment correctly', function () {
        $product = Product::factory()->create(['price' => 50, 'quantity' => 10]);
        $this->user->cart()->attach($product->id, ['quantity' => 3]);

        $this->postJson(route('orders.store'), [
            'customer_id' => null,
            'amount' => 150,
        ]);

        $order = Order::latest()->first();

        expect($order->items()->count())->toBe(1)
            ->and((float) $order->items()->first()->price)->toBe(150.0)
            ->and($order->payments()->count())->toBe(1)
            ->and((float) $order->payments()->first()->amount)->toBe(150.0);
    });

    test('order stores discounts on each item and totals use line discounts', function () {
        $product = Product::factory()->create([
            'price' => 100,
            'purchase_price' => 60,
            'quantity' => 10,
        ]);
        $this->user->cart()->attach($product->id, ['quantity' => 2]);

        $this->postJson(route('orders.store'), [
            'customer_id' => null,
            'amount' => 175,
            'item_discounts' => [
                $product->id => 25,
            ],
        ])->assertCreated();

        $order = Order::latest()->with('items')->first();
        $item = $order->items->first();

        expect($item->grossSubtotal())->toBe(200.0)
            ->and($item->discountAmount())->toBe(25.0)
            ->and($item->subtotal())->toBe(175.0)
            ->and($order->grossTotal())->toBe(200.0)
            ->and($order->discountAmount())->toBe(25.0)
            ->and($order->total())->toBe(175.0)
            ->and($order->costOfGoodsSold())->toBe(120.0);
    });

    test('order reduces product stock and empties cart', function () {
        $product = Product::factory()->create(['price' => 50, 'quantity' => 10]);
        $this->user->cart()->attach($product->id, ['quantity' => 3]);

        $this->postJson(route('orders.store'), [
            'customer_id' => null,
            'amount' => 150.0,
        ]);

        $product->refresh();

        expect($product->quantity)->toBe(7)
            ->and($this->user->cart()->count())->toBe(0);
    });

    test('order can be created without customer', function () {
        $product = Product::factory()->create(['price' => 100, 'quantity' => 10]);
        $this->user->cart()->attach($product->id, ['quantity' => 1]);

        $this->postJson(route('orders.store'), [
            'customer_id' => null,
            'amount' => 100,
        ]);

        expect(Order::latest()->first()->customer_id)->toBeNull();
    });

    test('loan order keeps the full amount pending for the customer', function () {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['price' => 125, 'quantity' => 10]);
        $this->user->cart()->attach($product->id, ['quantity' => 2]);

        $this->postJson(route('orders.store'), [
            'customer_id' => $customer->id,
            'payment_method' => 'loan',
            'amount' => 0,
            'payments' => [],
        ])->assertCreated();

        $order = Order::latest()->with(['items', 'payments'])->first();

        expect($order->payments)->toHaveCount(0)
            ->and($order->total())->toBe(250.0)
            ->and($order->receivedAmount())->toBe(0.0)
            ->and($order->remainingBalance())->toBe(250.0);
    });

    test('pos checkout customer balance payment clears previous customer dues', function () {
        $customer = Customer::factory()->create(['pending_amount' => 100]);
        $oldProduct = Product::factory()->create();
        $oldOrder = Order::factory()->create([
            'customer_id' => $customer->id,
            'user_id' => $this->user->id,
        ]);
        $oldOrder->items()->create([
            'product_id' => $oldProduct->id,
            'price' => 250,
            'quantity' => 1,
        ]);
        $oldOrder->payments()->create([
            'amount' => 50,
            'method' => 'cash',
            'user_id' => $this->user->id,
        ]);
        $newProduct = Product::factory()->create(['price' => 40, 'quantity' => 10]);
        $this->user->cart()->attach($newProduct->id, ['quantity' => 1]);

        expect($customer->fresh()->totalPendingBalance())->toBe(300.0);

        $this->postJson(route('orders.store'), [
            'customer_id' => $customer->id,
            'amount' => 40,
            'payment_method' => 'cash',
            'customer_balance_payment' => 300,
        ])->assertCreated();

        $oldOrder->refresh()->load(['items', 'payments']);
        $newOrder = Order::latest()->with(['items', 'payments'])->first();

        expect((float) $customer->refresh()->pending_amount)->toBe(0.0)
            ->and($oldOrder->remainingBalance())->toBe(0.0)
            ->and($newOrder->remainingBalance())->toBe(0.0)
            ->and($customer->totalPendingBalance())->toBe(0.0);
    });
});

describe('Order Validation', function () {
    test('customer_id must exist if provided', function () {
        $product = Product::factory()->create(['quantity' => 10]);
        $this->user->cart()->attach($product->id, ['quantity' => 1]);

        $response = $this->postJson(route('orders.store'), [
            'customer_id' => 99999,
            'amount' => 100,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('customer_id');
    });

    test('salesman cannot use a hidden customer on an order', function () {
        $salesman = User::factory()->salesman()->create();
        $customer = Customer::factory()->create(['is_visible_to_salesman' => false]);
        $product = Product::factory()->create(['quantity' => 10]);
        $salesman->cart()->attach($product->id, ['quantity' => 1]);

        $this->actingAs($salesman)
            ->postJson(route('orders.store'), [
                'customer_id' => $customer->id,
                'amount' => 100,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('customer_id');
    });

    test('amount is required and must be numeric', function () {
        $product = Product::factory()->create(['quantity' => 10]);
        $this->user->cart()->attach($product->id, ['quantity' => 1]);

        $this->postJson(route('orders.store'), ['customer_id' => null, 'amount' => ''])
            ->assertJsonValidationErrors('amount');

        $this->postJson(route('orders.store'), ['customer_id' => null, 'amount' => 'invalid'])
            ->assertJsonValidationErrors('amount');

        $this->postJson(route('orders.store'), ['customer_id' => null, 'amount' => -10])
            ->assertJsonValidationErrors('amount');
    });

    test('loan order requires a selected customer', function () {
        $product = Product::factory()->create(['price' => 100, 'quantity' => 10]);
        $this->user->cart()->attach($product->id, ['quantity' => 1]);

        $this->postJson(route('orders.store'), [
            'customer_id' => null,
            'payment_method' => 'loan',
            'amount' => 0,
            'payments' => [],
        ])->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Select a customer before creating a loan sale.',
            ]);
    });
});

describe('Partial Payment', function () {
    test('users can make partial payment on order', function () {
        $order = Order::factory()->create(['user_id' => $this->user->id]);
        $product = Product::factory()->create();

        $order->items()->create([
            'price' => 200,
            'quantity' => 1,
            'product_id' => $product->id,
        ]);

        $order->payments()->create(['amount' => 50, 'user_id' => $this->user->id]);

        $response = $this->post(route('orders.partial-payment'), [
            'order_id' => $order->id,
            'amount' => 100,
        ]);

        $response->assertRedirect(route('orders.index'))
            ->assertSessionHas('success');

        expect($order->payments()->count())->toBe(2)
            ->and($order->receivedAmount())->toBe(150.0);
    });

    test('partial payment cannot exceed remaining balance', function () {
        $order = Order::factory()->create(['user_id' => $this->user->id]);
        $product = Product::factory()->create();

        $order->items()->create(['price' => 100, 'quantity' => 1, 'product_id' => $product->id]);
        $order->payments()->create(['amount' => 50, 'user_id' => $this->user->id]);

        $response = $this->post(route('orders.partial-payment'), [
            'order_id' => $order->id,
            'amount' => 100,
        ]);

        $response->assertRedirect(route('orders.index'))
            ->assertSessionHasErrors();
    });

    test('order list payment updates customer total pending balance', function () {
        $customer = Customer::factory()->create(['pending_amount' => 25]);
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'user_id' => $this->user->id,
        ]);
        $product = Product::factory()->create();
        $order->items()->create([
            'price' => 100,
            'quantity' => 1,
            'product_id' => $product->id,
        ]);
        $order->payments()->create([
            'amount' => 20,
            'method' => 'cash',
            'user_id' => $this->user->id,
        ]);

        expect($customer->fresh()->totalPendingBalance())->toBe(105.0);

        $this->post(route('orders.partial-payment'), [
            'order_id' => $order->id,
            'amount' => 80,
            'payment_method' => 'cash',
        ])->assertRedirect(route('orders.index'));

        $order->refresh()->load(['items', 'payments']);

        expect($order->remainingBalance())->toBe(0.0)
            ->and($customer->fresh()->totalPendingBalance())->toBe(25.0);
    });
});

describe('Sales Return / Invoice Cancellation', function () {
    test('partial product return adds stock back and adjusts customer balance', function () {
        $product = Product::factory()->create([
            'price' => 50,
            'purchase_price' => 30,
            'quantity' => 7,
            'track_stock' => true,
        ]);
        $order = Order::factory()->create(['user_id' => $this->user->id]);
        $item = $order->items()->create([
            'price' => 150,
            'quantity' => 3,
            'unit_cost' => 30,
            'product_id' => $product->id,
        ]);
        $order->payments()->create(['amount' => 50, 'user_id' => $this->user->id]);

        expect($order->remainingBalance())->toBe(100.0);

        $this->post(route('orders.return.store', $order), [
            'type' => 'partial',
            'reason' => 'Customer returned one pack',
            'items' => [$item->id => 1],
        ])->assertRedirect(route('orders.index'))
            ->assertSessionHas('success');

        $order->refresh()->load(['items', 'payments']);
        $product->refresh();

        expect((float) $item->refresh()->returned_quantity)->toBe(1.0)
            ->and((float) $product->quantity)->toBe(8.0)
            ->and($order->total())->toBe(100.0)
            ->and($order->remainingBalance())->toBe(50.0)
            ->and($order->costOfGoodsSold())->toBe(60.0);
    });

    test('full invoice return cancels invoice and removes it from net sales report', function () {
        $product = Product::factory()->create([
            'price' => 100,
            'purchase_price' => 70,
            'quantity' => 4,
            'track_stock' => true,
        ]);
        $order = Order::factory()->create(['user_id' => $this->user->id]);
        $item = $order->items()->create([
            'price' => 200,
            'quantity' => 2,
            'unit_cost' => 70,
            'product_id' => $product->id,
        ]);
        $order->payments()->create(['amount' => 100, 'user_id' => $this->user->id]);

        $this->post(route('orders.return.store', $order), [
            'type' => 'full',
            'reason' => 'Invoice cancellation',
            'items' => [$item->id => 0],
        ])->assertRedirect(route('orders.index'))
            ->assertSessionHas('success');

        $order->refresh()->load(['items', 'payments']);
        $product->refresh();

        expect($order->isCancelled())->toBeTrue()
            ->and($order->returnedAmount())->toBe(200.0)
            ->and($order->total())->toBe(0.0)
            ->and((float) $product->quantity)->toBe(6.0);

        $this->get(route('reports.business', [
            'date_from' => now()->toDateString(),
            'date_to' => now()->toDateString(),
        ]))->assertViewHas('sales', 0.0)
            ->assertViewHas('grossProfit', 0.0)
            ->assertViewHas('topProducts', fn ($products) => $products->isEmpty());
    });
});
