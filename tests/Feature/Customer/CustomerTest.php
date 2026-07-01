<?php

declare(strict_types=1);

use App\Models\Customer;
use App\Models\AuditLog;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    $this->validCustomerData = [
        'customer_code' => 'MANUAL-001',
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john@example.com',
        'phone' => '3001234567',
        'address' => '123 Main St, City',
        'pending_amount' => '0',
    ];
});

describe('Customers Index', function () {
    test('authenticated users can view customers index', function () {
        $this->get(route('customers.index'))
            ->assertViewHas('customers')
            ->assertViewIs('customers.index')
            ->assertOk()
            ->assertSee('Customer List');
    });

    test('guests cannot view index', function () {
        auth()->logout();
        $this->get(route('customers.index'))
            ->assertRedirect(route('login'))
            ->assertStatus(302);
    });

    test('customers are paginated', function () {
        Customer::factory(15)
            ->withOutEmail()
            ->create();

        $response = $this->get(route('customers.index'));

        $response->assertViewHas('customers', function ($customers) {
            return $customers->count() === 10;
        });
    });

    test('customers index returns json when requested', function () {
        Customer::factory(5)
            ->withOutEmail()
            ->create();

        $response = $this->getJson(route('customers.index'));

        $response->assertOk()
            ->assertJsonCount(5)
            ->assertJsonStructure([
                '*' => ['id', 'customer_code', 'first_name', 'last_name', 'email', 'phone', 'address']
            ]);
    });

    test('json response returns all customers without pagination', function () {
        Customer::factory(15)->create();
        $this->getJson(route('customers.index'))
            ->assertJsonCount(15)
            ->assertOk();
    });

    test('salesman only receives customers allowed by manager', function () {
        $salesman = User::factory()->salesman()->create();
        $visibleCustomer = Customer::factory()->create(['is_visible_to_salesman' => true]);
        $hiddenCustomer = Customer::factory()->create(['is_visible_to_salesman' => false]);

        $this->actingAs($salesman)
            ->getJson(route('customers.index'))
            ->assertOk()
            ->assertJsonFragment(['id' => $visibleCustomer->id])
            ->assertJsonMissing(['id' => $hiddenCustomer->id]);
    });

    test('customers can be searched by name code email phone or address', function () {
        Customer::factory()->create([
            'customer_code' => 'VIP-001',
            'first_name' => 'Ayesha',
            'last_name' => 'Khan',
            'email' => 'ayesha@example.com',
            'phone' => '03001234567',
            'address' => 'Market Road',
        ]);
        Customer::factory()->create([
            'customer_code' => 'REG-002',
            'first_name' => 'Bilal',
            'last_name' => 'Ahmed',
            'email' => 'bilal@example.com',
            'phone' => '03111234567',
            'address' => 'Garden Town',
        ]);

        $this->get(route('customers.index', ['search' => 'Ayesha']))
            ->assertOk()
            ->assertSee('Ayesha')
            ->assertDontSee('Bilal');

        $this->getJson(route('customers.index', ['search' => 'Garden']))
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonFragment(['first_name' => 'Bilal']);
    });
});

describe('Customer Create', function () {
    test('authenticated users can view create form', function () {
        $this->get(route('customers.create'))
            ->assertViewIs('customers.create')
            ->assertSee('Create Customer')
            ->assertOk();
    });

    test('salesman cannot view create form', function () {
        $this->actingAs(User::factory()->salesman()->create())
            ->get(route('customers.create'))
            ->assertForbidden();
    });

    test('guests cannot be create view', function () {
        auth()->logout();
        $this->get(route('customers.create'))
            ->assertRedirect(route('login'))
            ->assertStatus(302);
    });
});

describe('Customer Store', function () {
    test('authenticated users can create a customer', function () {
        $response = $this->post(route('customers.store', $this->validCustomerData))
            ->assertRedirect(route('customers.index'))
            ->assertSessionHas('success')
            ->assertStatus(302);

        $this->assertDatabaseHas('customers', [
            'customer_code' => 'MANUAL-001',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'phone' => '+923001234567',
            'pending_amount' => '0.00',
            'user_id' => $this->user->id,
        ]);
    });

    test('customer is associated with authenticated user', function () {
        $this->post(route('customers.store', $this->validCustomerData));
        $customer = Customer::firstWhere('email', 'john@example.com');

        expect($customer->user_id)
            ->toBe($this->user->id);
    });

    test('customer stores the manually entered customer code', function () {
        $this->post(route('customers.store'), $this->validCustomerData)
            ->assertRedirect(route('customers.index'));

        $customer = Customer::firstWhere('email', 'john@example.com');

        expect($customer->customer_code)
            ->toBe('MANUAL-001');
    });

    test('customer code is required', function () {
        $this->post(route('customers.store'), array_merge($this->validCustomerData, [
            'customer_code' => '',
        ]))->assertSessionHasErrors('customer_code');
    });

    test('customer code must be unique when provided', function () {
        Customer::factory()->create(['customer_code' => 'CUST-SPECIAL']);

        $this->post(route('customers.store'), array_merge($this->validCustomerData, [
            'customer_code' => 'CUST-SPECIAL',
        ]))->assertSessionHasErrors('customer_code');
    });

    test('customer can be created with only required fields', function () {
        $data = [
            'customer_code' => 'MIN-001',
            'first_name' => 'Jane',
            'last_name' => 'Smith',
        ];

        $response = $this->post(route('customers.store'), $data);

        $response->assertRedirect(route('customers.index'));

        $this->assertDatabaseHas('customers', [
            'customer_code' => 'MIN-001',
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => null,
            'phone' => null,
            'address' => null,
        ]);
    });

    test('customer can be created with pending amount', function () {
        $this->post(route('customers.store'), array_merge($this->validCustomerData, [
            'email' => 'pending@example.com',
            'pending_amount' => '1250.50',
        ]))->assertRedirect(route('customers.index'));

        $this->assertDatabaseHas('customers', [
            'email' => 'pending@example.com',
            'pending_amount' => '1250.50',
        ]);
    });

    test('salesman cannot create a customer', function () {
        $this->actingAs(User::factory()->salesman()->create())
            ->post(route('customers.store'), $this->validCustomerData)
            ->assertForbidden();
    });
});

describe('Customer Edit', function () {
    test('authenticated user can view edit form', function () {
        $customer = Customer::factory()->create();

        $this->get(route('customers.edit', $customer))
            ->assertViewIs('customers.edit')
            ->assertViewHas('customer', $customer)
            ->assertSee('Update Customer')
            ->assertOk();
    });

    test('guests cannot view edit form', function () {
        auth()->logout();
        $customer = Customer::factory()->create();

        $this->get(route('customers.edit', $customer))
            ->assertRedirect(route('login'))
            ->assertStatus(302);
    });
});

describe('Customer Update', function () {
    test('authenticated users can update a customer', function () {
        $customer = Customer::factory()->create();

        $updateData = [
            'customer_code' => 'UPDATED-001',
            'first_name' => 'Updated',
            'last_name' => 'Name',
            'email' => 'updated@example.com',
            'phone' => '3217654321',
            'address' => 'New Address',
            'pending_amount' => '300.25',
        ];

        $response = $this->put(route('customers.update', $customer), $updateData);

        $response->assertRedirect(route('customers.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'customer_code' => 'UPDATED-001',
            'first_name' => 'Updated',
            'last_name' => 'Name',
            'email' => 'updated@example.com',
            'phone' => '+923217654321',
            'pending_amount' => '300.25',
        ]);
    });

    test('customer phone must be 10 digits after pakistan country code', function () {
        $this->post(route('customers.store'), array_merge($this->validCustomerData, [
            'phone' => '300123456',
        ]))->assertSessionHasErrors('phone');

        $this->post(route('customers.store'), array_merge($this->validCustomerData, [
            'customer_code' => 'PHONE-OK',
            'email' => 'phone-ok@example.com',
            'phone' => '+923001234567',
        ]))->assertRedirect(route('customers.index'));

        $this->assertDatabaseHas('customers', [
            'customer_code' => 'PHONE-OK',
            'phone' => '+923001234567',
        ]);
    });

    test('update works with customer fields', function () {
        $customer = Customer::factory()->create([
            'first_name' => 'Original'
        ]);

        $updateCustomerData = [
            'customer_code' => $customer->customer_code,
            'first_name' => 'Modified',
            'last_name' => $customer->last_name,
        ];

        $response = $this->put(route('customers.update', $customer), $updateCustomerData);
        $response->assertSessionHasNoErrors();

        $customer->refresh();

        expect($customer->first_name)->toBe('Modified');
    });

    test('user_id cannot be changed during update', function () {
        $originalUserId = $this->user->id;
        $customer = Customer::factory()->create(['user_id' => $originalUserId]);

        $otherUser = User::factory()->create();

        $updateData = array_merge($this->validCustomerData, ['user_id' => $otherUser->id]);

        $this->put(route('customers.update', $customer), $updateData);

        $customer->refresh();
        expect($customer->user_id)->toBe($originalUserId);
    });
});

describe('Customer Destroy', function () {
    test('authenticated users can delete a customer', function () {
        $customer = Customer::factory()->create();
        $response = $this->deleteJson(route('customers.destroy', $customer));

        $response->assertJson([
            'success' => true
        ])->assertOk();

        $this->assertDatabaseMissing('customers', [
            'id' => $customer->id
        ]);
    });

    test('guests cannot delete customer',function (){
        auth()->logout();
        $customer = Customer::factory()->create();
        $this->deleteJson(route('customers.destroy', $customer))
            ->assertUnauthorized();
    });
});

describe('Customer Salesman Visibility', function () {
    test('manager can toggle customer visibility for salesman', function () {
        $manager = User::factory()->manager()->create();
        $customer = Customer::factory()->create(['is_visible_to_salesman' => true]);

        $this->actingAs($manager)
            ->patch(route('customers.toggle-salesman-visibility', $customer))
            ->assertRedirect(route('customers.index'));

        expect($customer->refresh()->is_visible_to_salesman)->toBeFalse();
    });
});

describe('Customer Previous Balance Payment', function () {
    test('manager can receive customer previous balance payment', function () {
        $customer = Customer::factory()->create(['pending_amount' => 312]);

        $this->post(route('customers.opening-payment', $customer), [
            'amount' => '112.50',
            'payment_method' => 'cash',
            'note' => 'Received at counter',
        ])->assertRedirect();

        $customer->refresh();

        expect((float) $customer->pending_amount)->toBe(199.5);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'customer.opening_payment',
            'auditable_type' => Customer::class,
            'auditable_id' => $customer->id,
        ]);

        $paymentLog = AuditLog::where('action', 'customer.opening_payment')->first();

        expect((float) $paymentLog->properties['amount'])->toBe(112.5)
            ->and($paymentLog->properties['payment_method'])->toBe('cash')
            ->and($paymentLog->properties['note'])->toBe('Received at counter');
    });

    test('previous balance payment cannot exceed customer pending amount', function () {
        $customer = Customer::factory()->create(['pending_amount' => 50]);

        $this->post(route('customers.opening-payment', $customer), [
            'amount' => '75.00',
            'payment_method' => 'cash',
        ])->assertSessionHasErrors();

        expect((float) $customer->refresh()->pending_amount)->toBe(50.0);
    });
});
