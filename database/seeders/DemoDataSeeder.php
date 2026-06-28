<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Category;
use App\Models\Expense;
use App\Models\Order;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $user = User::where('email', 'admin@gmail.com')->firstOrFail();

            $categories = collect([
                'Grocery',
                'Drinks',
                'Bakery',
                'Household',
                'Health',
            ])->map(fn (string $name): Category => Category::updateOrCreate(
                ['name' => $name],
                ['description' => "{$name} products", 'status' => true]
            ));

            $products = collect([
                ['name' => 'Arabica Coffee Beans', 'sku' => 'SKU-000001', 'price' => 18.50, 'purchase_price' => 11.25, 'quantity' => 80, 'category' => 'Grocery'],
                ['name' => 'Green Tea Box', 'sku' => 'SKU-000002', 'price' => 7.99, 'purchase_price' => 4.10, 'quantity' => 65, 'category' => 'Drinks'],
                ['name' => 'Chocolate Cookies', 'sku' => 'SKU-000003', 'price' => 4.50, 'purchase_price' => 2.20, 'quantity' => 120, 'category' => 'Bakery'],
                ['name' => 'Organic Honey Jar', 'sku' => 'SKU-000004', 'price' => 12.00, 'purchase_price' => 7.50, 'quantity' => 34, 'category' => 'Grocery'],
                ['name' => 'Olive Oil Bottle', 'sku' => 'SKU-000005', 'price' => 16.75, 'purchase_price' => 10.00, 'quantity' => 42, 'category' => 'Grocery'],
                ['name' => 'Pasta Pack', 'sku' => 'SKU-000006', 'price' => 3.25, 'purchase_price' => 1.65, 'quantity' => 95, 'category' => 'Grocery'],
                ['name' => 'Tomato Sauce', 'sku' => 'SKU-000007', 'price' => 5.40, 'purchase_price' => 2.85, 'quantity' => 52, 'category' => 'Grocery'],
                ['name' => 'Almond Milk', 'sku' => 'SKU-000008', 'price' => 4.80, 'purchase_price' => 2.95, 'quantity' => 28, 'category' => 'Drinks'],
                ['name' => 'Granola Cereal', 'sku' => 'SKU-000009', 'price' => 8.25, 'purchase_price' => 4.75, 'quantity' => 9, 'category' => 'Grocery'],
                ['name' => 'Sparkling Water', 'sku' => 'SKU-000010', 'price' => 1.99, 'purchase_price' => 0.90, 'quantity' => 150, 'category' => 'Drinks'],
            ])->map(fn (array $product): Product => Product::updateOrCreate(
                ['sku' => $product['sku']],
                [
                    'name' => $product['name'],
                    'sku' => $product['sku'],
                    'category_id' => $categories->firstWhere('name', $product['category'])?->id,
                    'description' => 'Demo inventory item',
                    'image' => null,
                    'price' => $product['price'],
                    'purchase_price' => $product['purchase_price'],
                    'quantity' => $product['quantity'],
                    'status' => true,
                ]
            ));

            $customers = collect([
                ['first_name' => 'Walk-in', 'last_name' => 'Customer', 'email' => 'walkin@example.com', 'phone' => '555-0100'],
                ['first_name' => 'Sara', 'last_name' => 'Khan', 'email' => 'sara.khan@example.com', 'phone' => '555-0101'],
                ['first_name' => 'Ahmed', 'last_name' => 'Raza', 'email' => 'ahmed.raza@example.com', 'phone' => '555-0102'],
                ['first_name' => 'Mina', 'last_name' => 'Patel', 'email' => 'mina.patel@example.com', 'phone' => '555-0103'],
                ['first_name' => 'Omar', 'last_name' => 'Ali', 'email' => 'omar.ali@example.com', 'phone' => '555-0104'],
            ])->map(fn (array $customer): Customer => Customer::updateOrCreate(
                ['email' => $customer['email']],
                $customer + ['address' => 'Demo address', 'avatar' => null, 'user_id' => $user->id]
            ));

            $suppliers = collect([
                ['first_name' => 'North', 'last_name' => 'Foods', 'email' => 'north.foods@example.com', 'phone' => '555-0201'],
                ['first_name' => 'Metro', 'last_name' => 'Supplies', 'email' => 'metro.supplies@example.com', 'phone' => '555-0202'],
                ['first_name' => 'Fresh', 'last_name' => 'Market', 'email' => 'fresh.market@example.com', 'phone' => '555-0203'],
                ['first_name' => 'Daily', 'last_name' => 'Wholesale', 'email' => 'daily.wholesale@example.com', 'phone' => '555-0204'],
            ])->map(fn (array $supplier): Supplier => Supplier::updateOrCreate(
                ['email' => $supplier['email']],
                $supplier + ['address' => 'Demo supplier address', 'avatar' => null]
            ));

            if (Purchase::count() === 0) {
                foreach ($suppliers->take(3)->values() as $index => $supplier) {
                    $items = $products->slice($index * 2, 3)->values();
                    $total = $items->sum(fn (Product $product): float => ((float) $product->purchase_price) * (5 + $index));

                    $purchase = Purchase::create([
                        'supplier_id' => $supplier->id,
                        'user_id' => $user->id,
                        'purchase_date' => now()->subDays(7 - $index)->toDateString(),
                        'total_amount' => $total,
                        'status' => $index === 1 ? 'pending' : 'completed',
                        'notes' => 'Demo purchase order',
                    ]);

                    foreach ($items as $product) {
                        $purchase->items()->create([
                            'product_id' => $product->id,
                            'quantity' => 5 + $index,
                            'purchase_price' => $product->purchase_price,
                        ]);
                    }
                }
            }

            if (Order::count() === 0) {
                foreach ($customers->take(4)->values() as $index => $customer) {
                    $items = $products->slice($index, 2)->values();
                    $order = Order::create([
                        'customer_id' => $customer->id,
                        'user_id' => $user->id,
                        'created_at' => now()->subDays($index),
                        'updated_at' => now()->subDays($index),
                    ]);

                    $total = 0;
                    foreach ($items as $product) {
                        $quantity = $index + 1;
                        $lineTotal = ((float) $product->price) * $quantity;
                        $total += $lineTotal;

                        $order->items()->create([
                            'product_id' => $product->id,
                            'quantity' => $quantity,
                            'price' => $lineTotal,
                        ]);
                    }

                    $order->payments()->create([
                        'amount' => $index === 2 ? $total / 2 : $total,
                        'method' => $index === 1 ? 'card' : 'cash',
                        'user_id' => $user->id,
                    ]);
                }
            }

            if (Expense::count() === 0) {
                foreach ([
                    ['expense_date' => now()->subDays(3)->toDateString(), 'category' => 'Rent', 'amount' => 450.00, 'description' => 'Shop rent'],
                    ['expense_date' => now()->subDays(2)->toDateString(), 'category' => 'Electricity', 'amount' => 85.50, 'description' => 'Utility bill'],
                    ['expense_date' => now()->subDay()->toDateString(), 'category' => 'Transport', 'amount' => 32.00, 'description' => 'Supplier delivery'],
                    ['expense_date' => now()->toDateString(), 'category' => 'Marketing', 'amount' => 60.00, 'description' => 'Local promotion'],
                ] as $expense) {
                    Expense::create($expense + ['user_id' => $user->id]);
                }
            }
        });
    }
}
