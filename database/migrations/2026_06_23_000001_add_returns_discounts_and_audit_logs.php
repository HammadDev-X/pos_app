<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            if (!Schema::hasColumn('orders', 'discount')) {
                $table->decimal('discount', 10, 2)->default(0)->after('user_id');
            }
            if (!Schema::hasColumn('orders', 'due_date')) {
                $table->date('due_date')->nullable()->after('discount');
            }
            if (!Schema::hasColumn('orders', 'status')) {
                $table->string('status')->default('active')->after('due_date');
            }
        });

        Schema::table('users', function (Blueprint $table): void {
            if (!Schema::hasColumn('users', 'role')) {
                $table->string('role')->default('admin')->after('password');
            }
        });

        Schema::table('order_items', function (Blueprint $table): void {
            if (!Schema::hasColumn('order_items', 'unit_cost')) {
                $table->decimal('unit_cost', 10, 2)->default(0)->after('quantity');
            }
            if (!Schema::hasColumn('order_items', 'returned_quantity')) {
                $table->decimal('returned_quantity', 10, 2)->default(0)->after('unit_cost');
            }
        });

        Schema::table('products', function (Blueprint $table): void {
            if (!Schema::hasColumn('products', 'wholesale_price')) {
                $table->decimal('wholesale_price', 10, 2)->nullable()->after('price');
            }
            if (!Schema::hasColumn('products', 'minimum_stock_level')) {
                $table->decimal('minimum_stock_level', 10, 2)->default(0)->after('quantity');
            }
        });

        Schema::table('purchases', function (Blueprint $table): void {
            if (!Schema::hasColumn('purchases', 'transport_cost')) {
                $table->decimal('transport_cost', 10, 2)->default(0)->after('total_amount');
            }
            if (!Schema::hasColumn('purchases', 'other_cost')) {
                $table->decimal('other_cost', 10, 2)->default(0)->after('transport_cost');
            }
        });

        Schema::table('purchase_items', function (Blueprint $table): void {
            if (!Schema::hasColumn('purchase_items', 'expiry_date')) {
                $table->date('expiry_date')->nullable()->after('purchase_price');
            }
        });

        DB::table('order_items')
            ->whereNotNull('product_id')
            ->where('unit_cost', 0)
            ->orderBy('id')
            ->get(['id', 'product_id'])
            ->each(function ($item): void {
                $purchasePrice = DB::table('products')->where('id', $item->product_id)->value('purchase_price') ?? 0;

                DB::table('order_items')
                    ->where('id', $item->id)
                    ->update(['unit_cost' => $purchasePrice]);
            });

        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");

        if (Schema::hasTable('stock_adjustments')) {
            if ($driver === 'mysql') {
                DB::statement('ALTER TABLE `stock_adjustments` MODIFY `quantity` DECIMAL(10,2) NOT NULL');
                DB::statement('ALTER TABLE `stock_adjustments` MODIFY `quantity_before` DECIMAL(10,2) NOT NULL');
                DB::statement('ALTER TABLE `stock_adjustments` MODIFY `quantity_after` DECIMAL(10,2) NOT NULL');
            } else {
                Schema::table('stock_adjustments', function (Blueprint $table): void {
                    $table->decimal('quantity', 10, 2)->change();
                    $table->decimal('quantity_before', 10, 2)->change();
                    $table->decimal('quantity_after', 10, 2)->change();
                });
            }
        }

        Schema::create('order_returns', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type')->default('partial');
            $table->string('reason');
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->timestamps();

            $table->index(['order_id', 'type']);
        });

        Schema::create('order_return_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_return_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('quantity', 10, 2);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('unit_cost', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2);
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->nullableMorphs('auditable');
            $table->json('properties')->nullable();
            $table->timestamps();

            $table->index(['action', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('order_return_items');
        Schema::dropIfExists('order_returns');

        Schema::table('order_items', function (Blueprint $table): void {
            if (Schema::hasColumn('order_items', 'returned_quantity')) {
                $table->dropColumn('returned_quantity');
            }
            if (Schema::hasColumn('order_items', 'unit_cost')) {
                $table->dropColumn('unit_cost');
            }
        });

        Schema::table('orders', function (Blueprint $table): void {
            if (Schema::hasColumn('orders', 'status')) {
                $table->dropColumn('status');
            }
            if (Schema::hasColumn('orders', 'due_date')) {
                $table->dropColumn('due_date');
            }
            if (Schema::hasColumn('orders', 'discount')) {
                $table->dropColumn('discount');
            }
        });

        Schema::table('users', function (Blueprint $table): void {
            if (Schema::hasColumn('users', 'role')) {
                $table->dropColumn('role');
            }
        });

        Schema::table('products', function (Blueprint $table): void {
            if (Schema::hasColumn('products', 'minimum_stock_level')) {
                $table->dropColumn('minimum_stock_level');
            }
            if (Schema::hasColumn('products', 'wholesale_price')) {
                $table->dropColumn('wholesale_price');
            }
        });

        Schema::table('purchase_items', function (Blueprint $table): void {
            if (Schema::hasColumn('purchase_items', 'expiry_date')) {
                $table->dropColumn('expiry_date');
            }
        });

        Schema::table('purchases', function (Blueprint $table): void {
            if (Schema::hasColumn('purchases', 'other_cost')) {
                $table->dropColumn('other_cost');
            }
            if (Schema::hasColumn('purchases', 'transport_cost')) {
                $table->dropColumn('transport_cost');
            }
        });

        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");

        if (Schema::hasTable('stock_adjustments') && $driver === 'mysql') {
            DB::statement('ALTER TABLE `stock_adjustments` MODIFY `quantity` INT NOT NULL');
            DB::statement('ALTER TABLE `stock_adjustments` MODIFY `quantity_before` INT NOT NULL');
            DB::statement('ALTER TABLE `stock_adjustments` MODIFY `quantity_after` INT NOT NULL');
        }
    }
};
